@extends('layouts.portal')

@section('title', __('Security & Integrity'))

@section('content')
    <x-page-header :title="__('Security & Integrity')" :subtitle="__('Sign-in history, public certificate checks and live data-consistency checks.')">
        <a href="{{ route('admin.activity') }}" class="btn btn-outline"><x-lucide-history class="size-4" />{{ __('Activity Log') }}</a>
    </x-page-header>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="{{ __('Sections') }}">
        @foreach (['logins' => ['log-in', __('Sign-ins')], 'verifications' => ['badge-check', __('Certificate checks')], 'integrity' => ['shield-check', __('Integrity checks')]] as $key => [$icon, $label])
            <a href="{{ route('admin.security', ['tab' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-outline' => $tab !== $key])>
                <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />{{ $label }}
            </a>
        @endforeach
    </nav>

    @if ($tab === 'logins')
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-stat :label="__('Successful sign-ins (24h)')" :value="$stats['success_24h']" icon="log-in" tone="emerald" />
            <x-stat :label="__('Failed sign-ins (24h)')" :value="$stats['failed_24h']" icon="shield-alert" :tone="$stats['failed_24h'] > 10 ? 'rose' : 'amber'" :href="route('admin.security', ['tab' => 'logins', 'filter' => 'failed'])" />
            <x-stat :label="__('IPs with 5+ failures (24h)')" :value="$stats['suspicious_ips']->count()" icon="globe-lock" :tone="$stats['suspicious_ips']->isNotEmpty() ? 'rose' : 'brand'"
                    :hint="$stats['suspicious_ips']->take(3)->map(fn ($r) => $r->ip_address.' ('.$r->attempts.')')->implode(' · ') ?: null" />
        </div>

        <div class="card">
            <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
                <input type="hidden" name="tab" value="logins">
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Search email or IP…') }}">
                <select name="filter" class="input sm:max-w-48" onchange="this.form.submit()" aria-label="{{ __('Filter') }}">
                    <option value="">{{ __('All events') }}</option>
                    <option value="failed" @selected(request('filter') === 'failed')>{{ __('Failed only') }}</option>
                </select>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('When') }}</th><th>{{ __('Account') }}</th><th>{{ __('Event') }}</th><th>{{ __('IP address') }}</th><th>{{ __('Device') }}</th></tr></thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td class="text-xs whitespace-nowrap text-muted">{{ $event->created_at?->translatedFormat('j M Y · H:i:s') }}</td>
                                <td><p>{{ $event->user?->name ?? '—' }}</p><p class="font-latin text-xs text-muted" dir="ltr">{{ $event->email }}</p></td>
                                <td><x-status :value="$event->successful ? 'success' : 'failed'" /> <span class="text-xs text-muted">{{ __('login_event.'.$event->event) }}</span></td>
                                <td class="font-latin text-xs" dir="ltr">{{ $event->ip_address }}</td>
                                <td class="max-w-xs truncate text-xs text-muted" dir="ltr" title="{{ $event->user_agent }}">{{ \Illuminate\Support\Str::limit($event->user_agent, 60) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="log-in" :title="__('No sign-in events yet')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $events->links() }}
        </div>
    @endif

    @if ($tab === 'verifications')
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-stat :label="__('Valid (30 days)')" :value="$stats['valid'] ?? 0" icon="badge-check" tone="emerald" />
            <x-stat :label="__('Revoked (30 days)')" :value="$stats['revoked'] ?? 0" icon="ban" tone="amber" />
            <x-stat :label="__('Not found (30 days)')" :value="$stats['not_found'] ?? 0" icon="circle-x" tone="rose" :hint="__('Many misses can mean someone is guessing serials.')" />
        </div>
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('When') }}</th><th>{{ __('Serial') }}</th><th>{{ __('Result') }}</th><th>{{ __('Certificate holder') }}</th><th>{{ __('IP address') }}</th></tr></thead>
                    <tbody>
                        @forelse ($lookups as $lookup)
                            <tr>
                                <td class="text-xs whitespace-nowrap text-muted">{{ $lookup->created_at?->translatedFormat('j M Y · H:i') }}</td>
                                <td class="font-latin text-xs" dir="ltr">{{ $lookup->serial }}</td>
                                <td><x-status :value="$lookup->result" /></td>
                                <td>{{ $lookup->certificate?->student?->full_name ?? '—' }}</td>
                                <td class="font-latin text-xs" dir="ltr">{{ $lookup->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="badge-check" :title="__('No certificate checks yet')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lookups->links() }}
        </div>
    @endif

    @if ($tab === 'integrity')
        @php($failing = collect($checks)->where('ok', false)->count())
        <div @class(['mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm', 'border-emerald-200 bg-emerald-50 text-emerald-800' => ! $failing, 'border-amber-200 bg-amber-50 text-amber-900' => $failing])>
            @if ($failing)<x-lucide-triangle-alert class="size-5" />@else<x-lucide-shield-check class="size-5" />@endif
            <p class="font-semibold">{{ $failing ? trans_choice(':count check needs attention.|:count checks need attention.', $failing, ['count' => $failing]) : __('All checks passed.') }}</p>
            <span class="ms-auto text-xs">{{ __('Checked :time', ['time' => now()->translatedFormat('H:i:s')]) }}</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($checks as $check)
                <div class="card p-5">
                    <div class="flex items-start gap-3">
                        <span @class(['grid size-10 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600' => $check['ok'], 'bg-rose-50 text-rose-600' => ! $check['ok']])>
                            @if ($check['ok'])<x-lucide-circle-check class="size-5" />@else<x-lucide-circle-x class="size-5" />@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-navy">{{ $check['title'] }}</p>
                            <p class="text-sm text-muted">{{ $check['description'] }}</p>
                            @unless ($check['ok'])
                                <p class="mt-2 text-sm font-semibold text-rose-700">{{ trans_choice(':count problem found|:count problems found', $check['count'], ['count' => $check['count']]) }}</p>
                                <ul class="mt-1 space-y-0.5 text-xs">
                                    @foreach ($check['examples'] as $example)
                                        <li>@if ($example['url'])<a href="{{ $example['url'] }}" class="text-brand hover:underline">{{ $example['label'] }}</a>@else{{ $example['label'] }}@endif</li>
                                    @endforeach
                                </ul>
                                @if ($check['key'] === 'ledger_coverage' && auth()->user()->canAdmin('ledger', 'manage'))
                                    <x-confirm-delete :action="route('admin.ledger.rebuild')" method="POST" icon="refresh-cw" :label="__('Rebuild ledger')" class="mt-3" :message="__('Re-post every invoice, payment and expense? Manual entries are kept.')" />
                                @endif
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
