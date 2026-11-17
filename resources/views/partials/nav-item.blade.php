@php
    $active = request()->routeIs($match ?? $route);
@endphp
<a href="{{ route($route) }}" @class(['nav-link', 'active' => $active]) @if ($active) aria-current="page" @endif>
    <x-dynamic-component :component="'lucide-'.$icon" class="size-5 shrink-0" />
    <span class="flex-1">{{ $label }}</span>
    @if (! empty($badge))
        <span class="rounded-full bg-gold px-2 py-0.5 text-[11px] font-bold text-navy">{{ $badge }}</span>
    @endif
</a>
