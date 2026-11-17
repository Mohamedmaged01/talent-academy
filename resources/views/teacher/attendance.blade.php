@extends('layouts.portal')

@section('title', __('Attendance'))

@section('content')
    <x-page-header :title="__('Attendance — :class', ['class' => $batch->name])" :subtitle="$date->translatedFormat('l، j F Y')" :back="route('teacher.batches.show', $batch)">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="input" onchange="this.form.submit()">
        </form>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('teacher.attendance.update', $batch) }}" class="card xl:col-span-2"
              x-data="{ setAll(status) { this.$root.querySelectorAll(`input[type=radio][value=${status}]`).forEach(r => r.checked = true) } }">
            @csrf @method('PUT')
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div class="card-header">
                <h2 class="card-title">{{ trans_choice(':count student|:count students', $students->count()) }}</h2>
                <div class="flex gap-1">
                    <button type="button" class="btn btn-ghost btn-sm" @click="setAll('present')"><x-lucide-check-check class="size-4" />{{ __('All present') }}</button>
                </div>
            </div>

            @if ($records->isNotEmpty())
                <p class="mx-5 mt-4 flex items-center gap-2 rounded-xl bg-sky-50 px-4 py-2 text-sm text-sky-800"><x-lucide-info class="size-4" />{{ __('Attendance for this day was already recorded. Saving will update it.') }}</p>
            @endif

            <ul class="divide-y divide-line">
                @forelse ($students as $student)
                    @php $current = old("status.{$student->id}", $records[$student->id]->status ?? 'present'); @endphp
                    <li class="flex flex-col gap-3 px-5 py-3 md:flex-row md:items-center">
                        <span class="flex flex-1 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand">{{ mb_substr($student->full_name, 0, 1) }}</span>
                            <span class="font-semibold text-navy">{{ $student->full_name }}</span>
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['present' => ['peer-checked:bg-emerald-600', __('Present')], 'late' => ['peer-checked:bg-sky-600', __('Late')], 'absent' => ['peer-checked:bg-rose-600', __('Absent')], 'excused' => ['peer-checked:bg-amber-500', __('Excused')]] as $status => [$checked, $label])
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[{{ $student->id }}]" value="{{ $status }}" class="peer sr-only" @checked($current === $status)>
                                    <span class="inline-block rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-muted transition peer-checked:border-transparent peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand/40 {{ $checked }}">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <input type="text" name="note[{{ $student->id }}]" value="{{ old("note.{$student->id}", $records[$student->id]->note ?? '') }}" placeholder="{{ __('Note') }}" class="input py-1.5 text-xs md:w-40">
                    </li>
                @empty
                    <li><x-empty icon="users" :title="__('No students enrolled yet')" /></li>
                @endforelse
            </ul>
            @if ($students->isNotEmpty())
                <div class="flex justify-end border-t border-line px-5 py-4">
                    <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save attendance') }}</button>
                </div>
            @endif
        </form>

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Recent sessions') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($history as $day)
                    @php $d = \Illuminate\Support\Carbon::parse($day->date); $rate = $day->total ? round($day->attended / $day->total * 100) : 0; @endphp
                    <li>
                        <a href="{{ route('teacher.attendance.edit', [$batch, 'date' => $d->toDateString()]) }}" @class(['flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas', 'bg-brand-50' => $d->isSameDay($date)])>
                            <span>{{ $d->translatedFormat('D j M') }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-xs text-muted">{{ $day->attended }}/{{ $day->total }}</span>
                                <span @class(['badge', 'bg-emerald-50 text-emerald-700' => $rate >= 80, 'bg-amber-50 text-amber-700' => $rate < 80])>{{ $rate }}%</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-muted">{{ __('No sessions recorded yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
