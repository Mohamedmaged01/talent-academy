@extends('layouts.portal')

@section('title', __('Backup & Recovery'))

@section('content')
    @php($manage = auth()->user()->canAdmin('backups', 'manage'))
    <x-page-header :title="__('Backup & Recovery')" :subtitle="__('Encrypted, checksummed copies of every table. A daily backup runs automatically at 02:30 when the scheduler is enabled.')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Backups stored')" :value="$backups->total()" icon="database-backup" />
        <x-stat :label="__('Last automatic backup')" :value="$lastScheduled?->created_at->diffForHumans() ?? __('Never')" icon="calendar-clock" :tone="$lastScheduled && $lastScheduled->created_at->gt(now()->subDays(2)) ? 'emerald' : 'amber'" />
        <x-stat :label="__('Tables covered')" :value="$tables" icon="table-2" tone="violet" />
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        @if ($manage)
            <form method="POST" action="{{ route('admin.backups.store') }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Back up now') }}</h2></div>
                <div class="grid gap-3 p-5">
                    <x-input name="label" :label="__('Label (optional)')" :placeholder="__('e.g. Before term registration')" />
                    <button class="btn btn-primary"><x-lucide-database-backup class="size-4" />{{ __('Create backup') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Upload a backup file') }}</h2></div>
                <div class="grid gap-3 p-5">
                    <input type="file" name="file" accept=".tbk" required class="input" aria-label="{{ __('Backup file') }}">
                    @error('file')<p class="field-error">{{ $message }}</p>@enderror
                    <p class="hint">{{ __('For moving to a new server. The file must come from an installation with the same APP_KEY.') }}</p>
                    <button class="btn btn-outline"><x-lucide-upload class="size-4" />{{ __('Upload') }}</button>
                </div>
            </form>
        @endif

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Recovery plan') }}</h2></div>
            <ol class="list-decimal space-y-2 p-5 ps-10 text-sm leading-6 text-muted">
                <li>{{ __('Keep a copy of the .env APP_KEY somewhere safe and offline. Backups cannot be read without it.') }}</li>
                <li>{{ __('Download a backup at least once a week and store it off the server.') }}</li>
                <li>{{ __('To recover: install the platform, set the same APP_KEY, run the migrations, upload the backup here and restore it.') }}</li>
                <li>{{ __('Uploaded files (photos, attachments, library files) live in storage/app: copy that folder too.') }}</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Backup') }}</th><th>{{ __('Created') }}</th><th>{{ __('Records') }}</th><th>{{ __('Size') }}</th><th>{{ __('Integrity') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr>
                            <td>
                                <p class="font-latin text-sm font-semibold text-navy" dir="ltr">{{ $backup->code }}</p>
                                <p class="text-xs text-muted">{{ __('backup.'.$backup->kind) }}@if ($backup->label) · {{ $backup->label }}@endif</p>
                                @if ($backup->restored_at)<p class="text-xs text-amber-700">{{ __('Restored :date', ['date' => $backup->restored_at->translatedFormat('j M Y H:i')]) }}</p>@endif
                            </td>
                            <td class="text-xs text-muted whitespace-nowrap">{{ $backup->created_at->translatedFormat('j M Y · H:i') }}<br>{{ $backup->creator?->name ?? __('Scheduler') }}</td>
                            <td class="tabular-nums">{{ number_format($backup->records_count) }}<p class="text-xs text-muted">{{ trans_choice(':count table|:count tables', $backup->tables_count, ['count' => $backup->tables_count]) }}</p></td>
                            <td class="text-xs tabular-nums" dir="ltr">{{ $backup->humanSize() }}</td>
                            <td>
                                @if ($backup->verified_at)
                                    <span class="badge bg-emerald-50 text-emerald-700"><x-lucide-shield-check class="size-3" />{{ __('Verified') }}</span>
                                @else
                                    <span class="badge bg-rose-50 text-rose-700"><x-lucide-shield-alert class="size-3" />{{ __('Failed check') }}</span>
                                @endif
                                <p class="font-latin text-[10px] text-muted" dir="ltr" title="SHA-256">{{ \Illuminate\Support\Str::limit($backup->checksum, 16, '…') }}</p>
                            </td>
                            <td class="text-end whitespace-nowrap" x-data="{ restore: false }">
                                <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-ghost btn-sm" title="{{ __('Download') }}"><x-lucide-download class="size-4" /></a>
                                @if ($manage)
                                    <form method="POST" action="{{ route('admin.backups.verify', $backup) }}" class="inline">@csrf<button class="btn btn-ghost btn-sm" title="{{ __('Verify') }}"><x-lucide-shield-check class="size-4" /></button></form>
                                    <button type="button" class="btn btn-outline btn-sm" @click="restore = ! restore"><x-lucide-history class="size-4" />{{ __('Restore') }}</button>
                                    <x-confirm-delete :action="route('admin.backups.destroy', $backup)" />
                                    <form method="POST" action="{{ route('admin.backups.restore', $backup) }}" x-show="restore" x-cloak class="mt-2 flex flex-col items-end gap-2 rounded-xl bg-rose-50 p-3 text-start text-xs text-rose-800">
                                        @csrf
                                        <p class="max-w-xs whitespace-normal">{{ __('All current data will be replaced by this backup. A safety copy is taken first. Type the backup code to confirm:') }}</p>
                                        <input name="confirm" required class="input py-1.5 font-latin text-xs" dir="ltr" placeholder="{{ $backup->code }}" autocomplete="off" aria-label="{{ __('Backup code') }}">
                                        <button class="btn btn-danger btn-sm"><x-lucide-history class="size-4" />{{ __('Restore this backup') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="database-backup" :title="__('No backups yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('confirm')<p class="field-error px-5 pb-3">{{ $message }}</p>@enderror
        {{ $backups->links() }}
    </div>

    @if ($restores->isNotEmpty())
        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">{{ __('Recent restores') }}</h2></div>
            <ul class="divide-y divide-line text-sm">
                @foreach ($restores as $log)
                    <li class="px-5 py-3"><span class="text-muted">{{ $log->created_at?->translatedFormat('j M Y H:i') }}</span> · {{ $log->user?->name ?? '—' }} · {{ $log->description }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
