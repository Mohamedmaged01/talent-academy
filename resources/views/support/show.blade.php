@extends('layouts.portal')

@section('title', $ticket->subject)

@section('content')
    <x-page-header :title="$ticket->subject" :subtitle="$ticket->number.' · '.__('ticket.'.$ticket->category)" :back="route('support.index')">
        <x-status :value="$ticket->status" />
        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('support.close', $ticket) }}" onsubmit="return confirm(@js(__('Close this ticket?')))">
                @csrf
                <button class="btn btn-outline btn-sm"><x-lucide-circle-check class="size-4" />{{ __('Close ticket') }}</button>
            </form>
        @endif
    </x-page-header>

    <div class="card mx-auto max-w-3xl">
        @include('support._thread')

        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('support.reply', $ticket) }}" class="flex flex-col gap-3 border-t border-line p-4 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="body" class="sr-only">{{ __('Reply') }}</label>
                    <textarea id="body" name="body" rows="2" required class="input" placeholder="{{ __('Add more details or reply…') }}" dir="auto"></textarea>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send') }}</button>
            </form>
        @else
            <p class="border-t border-line px-5 py-4 text-center text-sm text-muted">{{ __('This ticket is closed. Open a new one if you still need help.') }}</p>
        @endif
    </div>
@endsection
