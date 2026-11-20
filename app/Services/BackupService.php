<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Backup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Database backups. A backup is every table exported to JSON, gzip-compressed,
 * encrypted with the application key (AES-256) and stored on the private disk
 * with a SHA-256 checksum. It works the same on SQLite and MySQL.
 *
 * Restoring needs the same APP_KEY that created the backup, so keep a copy of
 * the key somewhere safe. Uploaded files (photos, attachments) are not included.
 */
class BackupService
{
    /** Framework bookkeeping and the backup registry itself are never exported or overwritten. */
    public const EXCLUDED = [
        'backups', 'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'sqlite_sequence',
    ];

    public const FORMAT = 'talent-academy-backup';

    /** @return list<string> tables in foreign-key order: parents before children */
    public function tables(): array
    {
        $tables = array_values(array_diff(Schema::getTableListing(Schema::getCurrentSchemaListing(), false), self::EXCLUDED));
        sort($tables);

        $deps = [];
        foreach ($tables as $table) {
            $deps[$table] = array_values(array_intersect(
                array_diff(array_column(Schema::getForeignKeys($table), 'foreign_table'), [$table]),
                $tables,
            ));
        }

        $ordered = [];
        $visit = function (string $table, array $path = []) use (&$visit, &$ordered, $deps) {
            if (in_array($table, $ordered, true) || in_array($table, $path, true)) {
                return;
            }
            foreach ($deps[$table] as $parent) {
                $visit($parent, [...$path, $table]);
            }
            $ordered[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $ordered;
    }

    public function create(string $kind = 'manual', ?string $label = null, ?int $userId = null): Backup
    {
        $tables = [];
        $summary = [];

        foreach ($this->tables() as $table) {
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $tables[$table] = $rows;
            $summary[$table] = count($rows);
        }

        $payload = json_encode([
            'format' => self::FORMAT,
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'driver' => DB::connection()->getDriverName(),
            'tables' => $tables,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

        $contents = Crypt::encryptString(base64_encode(gzencode($payload, 6)));
        $code = 'BK-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        $path = "backups/{$code}.tbk";
        Storage::disk('local')->put($path, $contents);

        return Backup::create([
            'code' => $code,
            'label' => $label,
            'kind' => $kind,
            'file_path' => $path,
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'tables_count' => count($summary),
            'records_count' => array_sum($summary),
            'summary' => $summary,
            'created_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    /** Re-hash the file and make sure it decrypts. */
    public function verify(Backup $backup): bool
    {
        try {
            $this->read($backup);
            $backup->update(['verified_at' => now()]);

            return true;
        } catch (Throwable) {
            $backup->update(['verified_at' => null]);

            return false;
        }
    }

    /** Decrypt and decode a backup. Throws when the file is missing, altered or unreadable. */
    public function read(Backup $backup): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($backup->file_path)) {
            throw new RuntimeException(__('The backup file is missing.'));
        }

        $contents = $disk->get($backup->file_path);

        if (! hash_equals($backup->checksum, hash('sha256', $contents))) {
            throw new RuntimeException(__('The backup file has been altered: its checksum does not match.'));
        }

        return $this->decode($contents);
    }

    private function decode(string $contents): array
    {
        try {
            $json = gzdecode(base64_decode(Crypt::decryptString($contents), true) ?: '');
        } catch (Throwable) {
            throw new RuntimeException(__('The backup could not be decrypted. It was made with a different application key.'));
        }

        $payload = $json ? json_decode($json, true) : null;

        if (! is_array($payload) || ($payload['format'] ?? null) !== self::FORMAT || ! is_array($payload['tables'] ?? null)) {
            throw new RuntimeException(__('This is not a Talent Academy backup file.'));
        }

        return $payload;
    }

    /**
     * Replace the current data with the backup's. A safety backup of the current
     * state is taken first. Columns that no longer exist are skipped, so a backup
     * from before a schema change still restores.
     */
    public function restore(Backup $backup, ?int $userId = null): Backup
    {
        $payload = $this->read($backup);
        $safety = $this->create('pre_restore', 'Before restoring '.$backup->code, $userId);
        $tables = $this->tables();

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($payload, $tables) {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table)->delete();
                }

                foreach ($tables as $table) {
                    $rows = $payload['tables'][$table] ?? [];

                    if ($rows === []) {
                        continue;
                    }

                    $columns = array_flip(Schema::getColumnListing($table));

                    foreach (array_chunk($rows, 200) as $chunk) {
                        DB::table($table)->insert(array_map(fn ($row) => array_intersect_key($row, $columns), $chunk));
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Cache::forget('settings.all');
        $backup->update(['restored_at' => now()]);

        ActivityLog::create([
            'user_id' => $userId && DB::table('users')->where('id', $userId)->exists() ? $userId : null,
            'action' => 'backup.restored',
            'description' => "Data restored from backup {$backup->code} (safety copy {$safety->code})",
            'ip_address' => request()?->ip(),
        ]);

        return $safety;
    }

    /** Register a backup file uploaded from another server (same APP_KEY required). */
    public function import(UploadedFile $file, ?int $userId = null): Backup
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $payload = $this->decode($contents);
        $summary = array_map('count', $payload['tables']);

        $code = 'BK-UP-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        $path = "backups/{$code}.tbk";
        Storage::disk('local')->put($path, $contents);

        return Backup::create([
            'code' => $code,
            'label' => Str::limit($file->getClientOriginalName(), 120),
            'kind' => 'uploaded',
            'file_path' => $path,
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'tables_count' => count($summary),
            'records_count' => array_sum($summary),
            'summary' => $summary,
            'created_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    public function delete(Backup $backup): void
    {
        Storage::disk('local')->delete($backup->file_path);
        $backup->delete();
    }

    /** Keep the newest $keep scheduled backups; delete older ones. */
    public function prune(int $keep): int
    {
        $old = Backup::where('kind', 'scheduled')->orderByDesc('created_at')->orderByDesc('id')->skip($keep)->take(PHP_INT_MAX)->get();
        $old->each(fn (Backup $b) => $this->delete($b));

        return $old->count();
    }
}
