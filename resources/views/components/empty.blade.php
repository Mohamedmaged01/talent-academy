@props(['icon' => 'inbox', 'title' => null, 'message' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-6" />
    </span>
    <p class="mt-4 font-semibold text-navy">{{ $title ?? __('Nothing here yet') }}</p>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
