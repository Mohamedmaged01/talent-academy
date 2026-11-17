@props(['label', 'value', 'icon' => 'activity', 'tone' => 'brand', 'hint' => null, 'href' => null])

@php
    $tones = [
        'brand' => ['bg-brand-50 text-brand', 'from-brand-50/70'],
        'emerald' => ['bg-emerald-50 text-emerald-600', 'from-emerald-50/70'],
        'violet' => ['bg-violet-50 text-violet-600', 'from-violet-50/70'],
        'amber' => ['bg-amber-50 text-amber-600', 'from-amber-50/70'],
        'rose' => ['bg-rose-50 text-rose-600', 'from-rose-50/70'],
    ][$tone] ?? ['bg-brand-50 text-brand', 'from-brand-50/70'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "card relative overflow-hidden bg-gradient-to-b {$tones[1]} to-white p-5 block ".($href ? 'transition hover:-translate-y-0.5 hover:shadow-md' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-muted">{{ $label }}</p>
            <p @class(['mt-2 font-extrabold tracking-tight text-navy tabular-nums whitespace-nowrap', 'text-2xl' => mb_strlen((string) $value) <= 10, 'text-xl' => mb_strlen((string) $value) > 10])>{{ $value }}</p>
            @if ($hint)
                <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
            @endif
        </div>
        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $tones[0] }}">
            <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
        </span>
    </div>
</{{ $tag }}>
