@extends('layouts.portal')

@section('title', $thread->subject)

@section('content')
    <x-page-header :title="$thread->subject" :subtitle="__('With :names', ['names' => $others->map(fn ($u) => $u->name.' ('.\App\Support\MessagingDirectory::roleLabel($u->role).')')->implode('، ')])" :back="route('messages.index')" />

    <div class="card mx-auto max-w-3xl">
        <ol class="space-y-4 p-5">
            @foreach ($thread->messages as $message)
                @php($mine = $message->sender?->is(auth()->user()))
                <li @class(['flex gap-3', 'flex-row-reverse' => $mine]) @if ($loop->last) id="latest" @endif>
                    <img src="{{ $message->sender?->avatarUrl() }}" alt="" class="size-9 shrink-0 rounded-full object-cover">
                    <div @class(['max-w-[80%] rounded-2xl px-4 py-3 text-sm', 'bg-brand text-white' => $mine, 'bg-canvas text-ink' => ! $mine])>
                        <p @class(['mb-1 text-xs font-semibold', 'text-white/80' => $mine, 'text-navy' => ! $mine])>{{ $message->sender?->name ?? __('Deleted user') }}</p>
                        <p class="leading-7 whitespace-pre-line" dir="auto">{{ $message->body }}</p>
                        <p @class(['mt-1 text-[11px]', 'text-white/70' => $mine, 'text-muted' => ! $mine])>{{ $message->created_at->translatedFormat('j M Y · H:i') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <form method="POST" action="{{ route('messages.reply', $thread) }}" class="flex flex-col gap-3 border-t border-line p-4 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="body" class="sr-only">{{ __('Reply') }}</label>
                <textarea id="body" name="body" rows="2" required class="input" placeholder="{{ __('Write a reply…') }}" dir="auto">{{ old('body') }}</textarea>
                @error('body')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send') }}</button>
        </form>
    </div>
@endsection
