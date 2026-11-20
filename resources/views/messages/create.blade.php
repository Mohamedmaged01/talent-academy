@extends('layouts.portal')

@section('title', __('New message'))

@section('content')
    <x-page-header :title="__('New message')" :back="route('messages.index')" />

    <form method="POST" action="{{ route('messages.store') }}" class="card max-w-3xl"
          x-data="{ q: '', selected: @js((string) old('recipient_id', $selected)) }">
        @csrf
        <div class="grid gap-4 p-6">
            <div>
                <label class="label" for="recipient-search">{{ __('To') }} <span class="text-red-500">*</span></label>
                <input id="recipient-search" type="search" x-model="q" class="input" placeholder="{{ __('Type a name to filter…') }}" autocomplete="off">
                <div class="mt-2 max-h-64 overflow-y-auto rounded-xl border border-line" role="radiogroup" aria-label="{{ __('Recipient') }}">
                    @forelse ($recipients as $row)
                        <label class="flex cursor-pointer items-center gap-3 border-b border-line px-3 py-2 last:border-0 hover:bg-canvas has-[:checked]:bg-brand-50"
                               x-show="q === '' || @js(mb_strtolower($row['user']->name.' '.$row['context'])).includes(q.toLowerCase())">
                            <input type="radio" name="recipient_id" value="{{ $row['user']->id }}" x-model="selected" required>
                            <img src="{{ $row['user']->avatarUrl() }}" alt="" class="size-8 rounded-full object-cover">
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-navy">{{ $row['user']->name }}</span><span class="block truncate text-xs text-muted">{{ $row['context'] }}</span></span>
                        </label>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-muted">{{ __('There is nobody you can message yet.') }}</p>
                    @endforelse
                </div>
                @error('recipient_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-input name="subject" :label="__('Subject')" required dir="auto" />
            <x-input name="body" :label="__('Message')" type="textarea" rows="6" required dir="auto" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send') }}</button>
        </div>
    </form>
@endsection
