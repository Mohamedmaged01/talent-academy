@extends('layouts.portal')

@section('title', __('Messages'))

@section('content')
    <x-page-header :title="__('Messages')" :subtitle="__('Private conversations. Only the people in a conversation can read it.')">
        @if (auth()->user()->isAdmin() && auth()->user()->canAdmin('communication', 'manage'))
            <a href="{{ route('admin.broadcast.create') }}" class="btn btn-outline"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Message a group') }}</a>
        @endif
        <a href="{{ route('messages.create') }}" class="btn btn-primary"><x-lucide-square-pen class="size-4" />{{ __('New message') }}</a>
    </x-page-header>

    <div class="card">
        <form method="GET" class="border-b border-line p-4">
            <input type="search" name="q" value="{{ request('q') }}" class="input sm:max-w-sm" placeholder="{{ __('Search by subject…') }}">
        </form>
        <ul class="divide-y divide-line">
            @forelse ($threads as $thread)
                @php
                    $others = $thread->otherParticipants(auth()->user());
                    $unread = $thread->isUnreadFor(auth()->user());
                    $last = $thread->latestMessage;
                @endphp
                <li>
                    <a href="{{ route('messages.show', $thread) }}" @class(['flex items-center gap-4 px-5 py-4 hover:bg-brand-50/40', 'bg-brand-50/60' => $unread])>
                        <div class="flex shrink-0 -space-x-3 rtl:space-x-reverse">
                            @foreach ($others->take(2) as $person)
                                <img src="{{ $person->avatarUrl() }}" alt="" class="size-10 rounded-full object-cover ring-2 ring-white">
                            @endforeach
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p @class(['truncate text-navy', 'font-bold' => $unread, 'font-semibold' => ! $unread])>{{ $others->pluck('name')->implode('، ') ?: __('Just you') }}</p>
                                @if ($unread)<span class="size-2 shrink-0 rounded-full bg-brand"></span>@endif
                            </div>
                            <p class="truncate text-sm text-ink">{{ $thread->subject }}</p>
                            @if ($last)<p class="truncate text-xs text-muted">{{ $last->sender?->is(auth()->user()) ? __('You').': ' : '' }}{{ \Illuminate\Support\Str::limit($last->body, 90) }}</p>@endif
                        </div>
                        <span class="shrink-0 text-xs text-muted">{{ $thread->last_message_at?->diffForHumans(short: true) }}</span>
                    </a>
                </li>
            @empty
                <li><x-empty icon="mail" :title="__('No messages yet')" :message="__('Start a conversation with your teachers, students, parents or the administration.')" /></li>
            @endforelse
        </ul>
        {{ $threads->links() }}
    </div>
@endsection
