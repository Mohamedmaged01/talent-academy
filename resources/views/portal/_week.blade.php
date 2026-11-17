{{-- Weekly timetable grid. Expects $slots (grouped by day_of_week), $days, $showTeacher, optional $linkRoute. --}}
@php $today = now()->dayOfWeek; @endphp
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
    @foreach ($days as $num => $day)
        @continue($num === 5 && empty($slots[5] ?? null))
        <section @class(['card overflow-hidden', 'ring-2 ring-brand' => $num === $today])>
            <header @class(['px-4 py-3 text-sm font-bold', 'bg-brand text-white' => $num === $today, 'bg-canvas text-navy' => $num !== $today])>
                {{ __($day) }} @if ($num === $today)<span class="ms-1 text-xs font-normal opacity-80">· {{ __('Today') }}</span>@endif
            </header>
            <div class="space-y-2 p-3">
                @forelse ($slots[$num] ?? [] as $slot)
                    @php $tag = isset($linkRoute) ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if (isset($linkRoute)) href="{{ route($linkRoute, $slot->batch) }}" @endif
                        @class(['block rounded-xl border-s-4 p-3 text-sm transition hover:shadow-sm', 'border-brand bg-brand-50/60' => $slot->batch->course->pathway === 'academic', 'border-gold bg-amber-50/60' => $slot->batch->course->pathway !== 'academic'])>
                        <p class="font-latin text-xs font-semibold text-muted" dir="ltr">{{ $slot->timeRange() }}</p>
                        <p class="mt-1 font-semibold leading-5 text-navy">{{ $slot->batch->name }}</p>
                        @if ($showTeacher && $slot->batch->teacher)
                            <p class="mt-1 text-xs text-muted">{{ $slot->batch->teacher->name }}</p>
                        @endif
                        @if ($slot->batch->room)
                            <p class="text-xs text-muted">{{ $slot->batch->room }}</p>
                        @endif
                    </{{ $tag }}>
                @empty
                    <p class="py-4 text-center text-xs text-muted">{{ __('No sessions') }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
