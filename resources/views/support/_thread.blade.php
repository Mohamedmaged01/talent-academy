{{-- Ticket conversation. Expects $ticket with replies.user and user loaded. --}}
<ol class="space-y-4 p-5">
    <li class="flex gap-3">
        <img src="{{ $ticket->user->avatarUrl() }}" alt="" class="size-9 shrink-0 rounded-full object-cover">
        <div class="min-w-0 flex-1 rounded-2xl bg-canvas px-4 py-3 text-sm">
            <p class="text-xs font-semibold text-navy">{{ $ticket->user->name }} · <span class="font-normal text-muted">{{ $ticket->created_at->translatedFormat('j M Y · H:i') }}</span></p>
            <p class="mt-1 leading-7 whitespace-pre-line" dir="auto">{{ $ticket->body }}</p>
        </div>
    </li>
    @foreach ($ticket->replies as $reply)
        @php($staff = $reply->user?->isAdmin())
        <li class="flex gap-3">
            <img src="{{ $reply->user?->avatarUrl() }}" alt="" class="size-9 shrink-0 rounded-full object-cover">
            <div @class(['min-w-0 flex-1 rounded-2xl px-4 py-3 text-sm', 'bg-brand-50 ring-1 ring-brand-100' => $staff, 'bg-canvas' => ! $staff])>
                <p class="text-xs font-semibold text-navy">
                    {{ $reply->user?->name ?? __('Deleted user') }}
                    @if ($staff)<span class="badge bg-brand text-white">{{ __('Academy') }}</span>@endif
                    · <span class="font-normal text-muted">{{ $reply->created_at->translatedFormat('j M Y · H:i') }}</span>
                </p>
                <p class="mt-1 leading-7 whitespace-pre-line" dir="auto">{{ $reply->body }}</p>
            </div>
        </li>
    @endforeach
</ol>
