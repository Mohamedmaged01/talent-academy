<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index(): View
    {
        return view('admin.backups.index', [
            'backups' => Backup::with('creator')->latest()->latest('id')->paginate(20),
            'tables' => count($this->backups->tables()),
            'lastScheduled' => Backup::where('kind', 'scheduled')->latest()->first(),
            'restores' => ActivityLog::where('action', 'backup.restored')->with('user')->latest('created_at')->take(5)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['label' => ['nullable', 'string', 'max:120']]);
        $backup = $this->backups->create('manual', $data['label'] ?? null, $request->user()->id);
        ActivityLog::record('backup.created', "Backup {$backup->code} created ({$backup->records_count} records)", $backup);

        return back()->with('success', __('Backup :code created.', ['code' => $backup->code]));
    }

    public function download(Backup $backup): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($backup->file_path), 404);
        ActivityLog::record('backup.downloaded', "Backup {$backup->code} downloaded", $backup);

        return Storage::disk('local')->download($backup->file_path, $backup->code.'.tbk');
    }

    public function verify(Backup $backup): RedirectResponse
    {
        return $this->backups->verify($backup)
            ? back()->with('success', __('Backup :code is intact and readable.', ['code' => $backup->code]))
            : back()->with('error', __('Backup :code failed verification.', ['code' => $backup->code]));
    }

    public function restore(Request $request, Backup $backup): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:'.$backup->code]], ['confirm.in' => __('Type the backup code exactly to confirm.')]);

        try {
            $safety = $this->backups->restore($backup, $request->user()->id);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('Restore failed: :message', ['message' => $e->getMessage()]));
        }

        // The account that ran the restore may not exist in the restored data.
        if (! User::whereKey($request->user()->id)->exists()) {
            auth()->logout();

            return redirect()->route('login')->with('success', __('Data restored. Please sign in again.'));
        }

        return redirect()->route('admin.backups.index')
            ->with('success', __('Data restored from :code. A safety copy of the previous data was saved as :safety.', ['code' => $backup->code, 'safety' => $safety->code]));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:204800']]);

        try {
            $backup = $this->backups->import($request->file('file'), $request->user()->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::record('backup.uploaded', "Backup file uploaded as {$backup->code}", $backup);

        return back()->with('success', __('Backup uploaded as :code. You can restore it from the list.', ['code' => $backup->code]));
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        ActivityLog::record('backup.deleted', "Backup {$backup->code} deleted");
        $this->backups->delete($backup);

        return back()->with('success', __('Backup deleted.'));
    }
}
