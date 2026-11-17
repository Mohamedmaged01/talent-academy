@extends('layouts.portal')

@section('title', __('Activity Log'))

@section('content')
    <x-page-header :title="__('Activity Log')" :subtitle="__('An audit trail of important actions across the platform.')" />

    <div class="card">
        <form method="GET" class="border-b border-line p-4">
            <div class="relative max-w-md">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search the log…') }}" class="input ps-9">
            </div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('When') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Details') }}</th><th>{{ __('IP') }}</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-muted" title="{{ $log->created_at }}">{{ $log->created_at?->translatedFormat('j M Y H:i') }}</td>
                            <td class="whitespace-nowrap">{{ $log->user?->name ?? __('System') }}</td>
                            <td><code class="rounded bg-canvas px-1.5 py-0.5 font-latin text-xs text-brand" dir="ltr">{{ $log->action }}</code></td>
                            <td class="text-sm" dir="auto">{{ $log->description }}</td>
                            <td class="font-latin text-xs text-muted" dir="ltr">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="history" :title="__('No activity yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
@endsection
