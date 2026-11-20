<?php

use App\Models\ActivityLog;
use App\Models\PermissionDelegation;
use App\Services\BackupService;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduled tasks need the system scheduler to run `php artisan schedule:run`
| every minute (cron on Linux; Task Scheduler on Windows / Plesk).
*/

Artisan::command('backups:run {--keep=14 : Number of scheduled backups to keep}', function (BackupService $backups) {
    $backup = $backups->create('scheduled', 'Scheduled backup');
    $pruned = $backups->prune((int) $this->option('keep'));
    ActivityLog::create(['action' => 'backup.created', 'description' => "Scheduled backup {$backup->code} created ({$backup->records_count} records)"]);

    $this->info("Backup {$backup->code} created ({$backup->records_count} records, {$backup->humanSize()}). Old backups removed: {$pruned}.");
})->purpose('Create an encrypted database backup and prune old scheduled ones');

Artisan::command('ledger:rebuild', function (LedgerService $ledger) {
    $result = $ledger->rebuild();
    ActivityLog::create(['action' => 'ledger.rebuilt', 'description' => "Automatic ledger postings rebuilt from the console ({$result['entries']} entries)"]);

    $this->info("Ledger rebuilt: {$result['entries']} automatic entries posted from invoices, payments and expenses.");
})->purpose('Re-post every invoice, payment and expense to the double-entry ledger (manual entries are kept)');

Artisan::command('delegations:expire', function () {
    // Delegations stop counting the moment they end; this only tidies their status for the screens.
    $count = PermissionDelegation::where('status', 'pending')->where('created_at', '<', now()->subDays(30))->update(['status' => 'rejected']);
    $this->info("Stale access requests closed: {$count}.");
})->purpose('Close access requests left pending for more than 30 days');

Schedule::command('backups:run')->dailyAt('02:30');
Schedule::command('delegations:expire')->daily();
