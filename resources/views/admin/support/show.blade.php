@extends('layouts.portal')

@section('title', $ticket->subject)

@section('content')
    <x-page-header :title="$ticket->subject" :subtitle="$ticket->number.' · '.$ticket->user->name.' · '.__('ticket.'.$ticket->category)" :back="route('admin.support.index')">
        <x-status :value="$ticket->priority" />
        <x-status :value="$ticket->status" />
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            @include('support._thread')

            @if (auth()->user()->canAdmin('support', 'manage'))
                <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" class="grid gap-3 border-t border-line p-4">
                    @csrf
                    <x-input name="body" :label="__('Reply')" type="textarea" rows="4" required dir="auto" />
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <x-select name="status" :label="__('Set status to')" :value="$ticket->status === 'open' ? 'in_progress' : $ticket->status"
                                  :options="collect(\App\Models\SupportTicket::STATUSES)->mapWithKeys(fn ($s) => [$s => __(\Illuminate\Support\Str::headline($s))])" />
                        <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send reply') }}</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Requester') }}</h2></div>
                <div class="space-y-2 p-5 text-sm">
                    <div class="flex items-center gap-3">
                        <img src="{{ $ticket->user->avatarUrl() }}" alt="" class="size-10 rounded-full object-cover">
                        <div><p class="font-semibold text-navy">{{ $ticket->user->name }}</p><p class="text-xs text-muted">{{ \App\Support\MessagingDirectory::roleLabel($ticket->user->role) }}</p></div>
                    </div>
                    <p class="text-muted" dir="ltr">{{ $ticket->user->email }}</p>
                    @if ($ticket->user->phone)<p class="text-muted" dir="ltr">{{ $ticket->user->phone }}</p>@endif
                    <a href="{{ route('messages.create', ['to' => $ticket->user_id]) }}" class="btn btn-outline btn-sm mt-2"><x-lucide-mail class="size-4" />{{ __('Send a private message') }}</a>
                </div>
            </div>
            @if (auth()->user()->canAdmin('support', 'manage'))
                <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="card">
                    @csrf @method('PATCH')
                    <div class="card-header"><h2 class="card-title">{{ __('Change status only') }}</h2></div>
                    <div class="flex gap-2 p-5">
                        <select name="status" class="input" aria-label="{{ __('Status') }}">
                            @foreach (\App\Models\SupportTicket::STATUSES as $status)
                                <option value="{{ $status }}" @selected($ticket->status === $status)>{{ __(\Illuminate\Support\Str::headline($status)) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline">{{ __('Save') }}</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
