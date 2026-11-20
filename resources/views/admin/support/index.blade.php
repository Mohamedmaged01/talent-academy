@extends('layouts.portal')

@section('title', __('Support Tickets'))

@section('content')
    <x-page-header :title="__('Support Tickets')" :subtitle="__('Requests from teachers, students and parents.')" />

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['active' => __('Open & in progress')] + collect(\App\Models\SupportTicket::STATUSES)->mapWithKeys(fn ($s) => [$s => __(\Illuminate\Support\Str::headline($s))])->all() + ['all' => __('All')] as $key => $label)
            <a href="{{ route('admin.support.index', ['status' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $status === $key, 'btn-outline' => $status !== $key])>
                {{ $label }}
                @if (isset($counts[$key]))<span class="tabular-nums opacity-70">({{ $counts[$key] }})</span>@endif
            </a>
        @endforeach
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Ticket') }}</th><th>{{ __('From') }}</th><th>{{ __('Category') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Last activity') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td><a href="{{ route('admin.support.show', $ticket) }}" class="font-semibold text-navy hover:text-brand">{{ $ticket->subject }}</a><p class="font-latin text-xs text-muted" dir="ltr">{{ $ticket->number }}</p></td>
                            <td><p>{{ $ticket->user->name }}</p><p class="text-xs text-muted">{{ \App\Support\MessagingDirectory::roleLabel($ticket->user->role) }}</p></td>
                            <td class="text-muted">{{ __('ticket.'.$ticket->category) }}</td>
                            <td><x-status :value="$ticket->priority" /></td>
                            <td class="text-xs text-muted whitespace-nowrap">{{ $ticket->last_reply_at?->diffForHumans() }} · {{ trans_choice(':count reply|:count replies', $ticket->replies_count, ['count' => $ticket->replies_count]) }}</td>
                            <td><x-status :value="$ticket->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="life-buoy" :title="__('No tickets here')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $tickets->links() }}
    </div>
@endsection
