@php
    $messages = [
        'success' => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'circle-check'],
        'warning' => ['bg-amber-50 text-amber-800 border-amber-200', 'triangle-alert'],
        'error' => ['bg-rose-50 text-rose-800 border-rose-200', 'circle-x'],
    ];
@endphp

@foreach ($messages as $key => [$classes, $icon])
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" x-transition role="status"
             class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $classes }}">
            <x-dynamic-component :component="'lucide-'.$icon" class="mt-0.5 size-4 shrink-0" />
            <p class="flex-1">{{ session($key) }}</p>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="{{ __('Close') }}">
                <x-lucide-x class="size-4" />
            </button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! ($hideErrorSummary ?? false))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" />
        <p>{{ __('Please review the highlighted fields.') }}</p>
    </div>
@endif
