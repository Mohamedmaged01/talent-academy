@extends('layouts.portal')

@section('title', __('Notifications'))

@section('content')
    <x-page-header :title="__('Notifications')">
        <a href="{{ route('notifications.index', request('filter') === 'unread' ? [] : ['filter' => 'unread']) }}" class="btn btn-outline">
            <x-lucide-filter class="size-4" />{{ request('filter') === 'unread' ? __('Show all') : __('Unread only') }}
        </a>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-primary"><x-lucide-check-check class="size-4" />{{ __('Mark all as read') }}</button>
        </form>
    </x-page-header>

    <div class="card mx-auto max-w-3xl">
        <ul class="divide-y divide-line">
            @forelse ($notifications as $notification)
                <li>
                    <a href="{{ route('notifications.open', $notification->id) }}" @class(['flex items-start gap-4 px-5 py-4 hover:bg-canvas', 'bg-brand-50/50' => ! $notification->read_at])>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand">
                            <x-dynamic-component :component="'lucide-'.($notification->data['icon'] ?? 'bell')" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block leading-7 text-ink', 'font-semibold' => ! $notification->read_at])>{{ \App\Notifications\PortalNotification::text($notification->data) }}</span>
                            <span class="text-xs text-muted">{{ $notification->created_at->translatedFormat('j M Y · H:i') }} · {{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                        @unless ($notification->read_at)<span class="mt-2 size-2.5 shrink-0 rounded-full bg-brand" aria-label="{{ __('Unread') }}"></span>@endunless
                    </a>
                </li>
            @empty
                <li><x-empty icon="bell" :title="__('You are all caught up.')" /></li>
            @endforelse
        </ul>
        {{ $notifications->links() }}
    </div>
@endsection
