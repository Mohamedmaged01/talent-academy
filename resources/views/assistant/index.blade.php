@extends('layouts.portal')

@section('title', __('Talent AI Assistant'))

@section('content')
    <x-page-header :title="__('Talent AI Assistant')" :subtitle="__('Ask questions, get summaries and draft messages. Only aggregate figures are shared with the AI service: no names, phone numbers or other personal details.')">
        @if ($history)
            <form method="POST" action="{{ route($prefix.'.assistant.clear') }}">@csrf @method('DELETE')<button class="btn btn-outline"><x-lucide-eraser class="size-4" />{{ __('New conversation') }}</button></form>
        @endif
    </x-page-header>

    @unless ($configured)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">{{ __('The assistant is switched off.') }}</p>
            <p class="mt-1">{{ __('To enable it, add a Google Gemini API key to the server\'s .env file:') }} <code class="font-latin" dir="ltr">GEMINI_API_KEY=…</code></p>
        </div>
    @endunless

    <div class="card mx-auto flex max-w-4xl flex-col">
        <div class="min-h-80 space-y-4 p-5">
            @forelse ($history as $turn)
                @php($mine = $turn['role'] === 'user')
                <div @class(['flex gap-3', 'flex-row-reverse' => $mine]) @if ($loop->last) id="latest" @endif>
                    @if ($mine)
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="size-9 shrink-0 rounded-full object-cover">
                    @else
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand to-violet-600 text-white"><x-lucide-sparkles class="size-4" /></span>
                    @endif
                    <div @class(['max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-7 whitespace-pre-line', 'bg-brand text-white' => $mine, 'bg-canvas text-ink' => ! $mine]) dir="auto">{{ $turn['text'] }}</div>
                </div>
            @empty
                <div class="py-10 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-brand to-violet-600 text-white"><x-lucide-sparkles class="size-7" /></span>
                    <p class="mt-4 font-bold text-navy">{{ __('How can I help today?') }}</p>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @foreach ($suggestions as $suggestion)
                            <form method="POST" action="{{ route($prefix.'.assistant.ask') }}">
                                @csrf
                                <input type="hidden" name="message" value="{{ $suggestion }}">
                                <button class="btn btn-outline btn-sm" @disabled(! $configured)>{{ $suggestion }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endforelse
        </div>

        <form method="POST" action="{{ route($prefix.'.assistant.ask') }}" class="flex flex-col gap-3 border-t border-line p-4 sm:flex-row sm:items-end" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <div class="flex-1">
                <label for="message" class="sr-only">{{ __('Your question') }}</label>
                <textarea id="message" name="message" rows="2" required maxlength="2000" class="input" placeholder="{{ __('Ask anything about the academy…') }}" dir="auto" @disabled(! $configured)>{{ old('message') }}</textarea>
                @error('message')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary" :disabled="busy || ! @js($configured)">
                <x-lucide-sparkles class="size-4" /><span x-text="busy ? @js(__('Thinking…')) : @js(__('Ask'))">{{ __('Ask') }}</span>
            </button>
        </form>
    </div>
@endsection
