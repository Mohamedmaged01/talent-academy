<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An encrypted, checksummed snapshot of the database tables (see App\Services\BackupService). */
class Backup extends Model
{
    public const KINDS = ['manual', 'scheduled', 'pre_restore', 'uploaded'];

    protected $fillable = [
        'code', 'label', 'kind', 'file_path', 'size_bytes', 'checksum', 'tables_count',
        'records_count', 'summary', 'created_by', 'verified_at', 'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'verified_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function humanSize(): string
    {
        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 2).' MB'
            : max(1, round($this->size_bytes / 1024)).' KB';
    }
}
