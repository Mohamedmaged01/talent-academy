@include('partials.nav-item', ['route' => 'parent.dashboard', 'match' => ['parent.dashboard', 'parent.children.*'], 'icon' => 'house', 'label' => __('My Children')])
@include('partials.nav-item', ['route' => 'parent.announcements', 'icon' => 'megaphone', 'label' => __('Announcements')])

<div class="mt-4 space-y-1">
    @foreach (auth()->user()->children()->orderBy('full_name')->get() as $child)
        <a href="{{ route('parent.children.show', $child) }}" @class(['nav-link', 'active' => request()->route('student')?->id === $child->id])>
            <span class="grid size-6 place-items-center rounded-full bg-gold text-[11px] font-bold text-navy">{{ mb_substr($child->full_name, 0, 1) }}</span>
            <span class="truncate">{{ $child->displayName() }}</span>
        </a>
    @endforeach
</div>
