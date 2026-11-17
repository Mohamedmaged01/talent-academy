@extends('layouts.portal')

@section('title', __('Dashboard'))

@section('content')
    <section class="card relative mb-6 overflow-hidden bg-gradient-to-l from-brand to-brand-dark p-6 text-white md:p-8">
        <div class="absolute -top-16 end-0 size-56 rounded-full bg-gold/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-200">{{ __('Welcome back') }}</p>
                <p class="mt-1 text-2xl font-extrabold md:text-3xl">{{ $student->displayName() }}</p>
                <p class="mt-2 text-sm text-slate-200">
                    <span class="font-latin" dir="ltr">{{ $student->code }}</span> · {{ __(ucfirst($student->pathway)) }} · {{ $student->grade_level }} · {{ $student->branch->tr('name') }}
                </p>
            </div>
            <a href="{{ route('student.schedule') }}" class="btn btn-gold self-start md:self-center"><x-lucide-calendar-days class="size-4" />{{ __('My Schedule') }}</a>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Attendance')" :value="pct($attendanceRate)" icon="clipboard-check" :tone="$attendanceRate !== null && $attendanceRate < 80 ? 'rose' : 'emerald'" :hint="__('Certificate requires 80%')" :href="route('student.attendance')" />
        <x-stat :label="__('Exam average')" :value="pct($average)" icon="chart-column" tone="violet" :href="route('student.grades')" />
        <x-stat :label="__('Balance due')" :value="money($balance, false)" icon="wallet" :tone="$balance > 0 ? 'amber' : 'emerald'" :href="route('student.payments')" />
        <x-stat :label="__('Certificates')" :value="$certificatesCount" icon="award" :href="route('student.certificates')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('My courses') }}</h2><a href="{{ route('student.courses') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a></div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    @forelse ($batches as $batch)
                        <a href="{{ route('student.courses.show', $batch) }}" class="flex gap-4 rounded-xl border border-line p-4 transition hover:border-brand">
                            <img src="{{ $batch->course->imageUrl() }}" alt="" class="size-16 shrink-0 rounded-xl object-cover">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-navy">{{ $batch->course->tr('title') }}</span>
                                <span class="block truncate text-xs text-muted">{{ $batch->teacher?->name }}</span>
                                <span class="mt-1 block truncate text-xs text-brand">{{ $batch->scheduleSummary() }}</span>
                            </span>
                        </a>
                    @empty
                        <x-empty icon="book-open" :title="__('You are not enrolled in any course yet')" class="md:col-span-2" />
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Assignments due') }}</h2><a href="{{ route('student.assignments.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a></div>
                <ul class="divide-y divide-line">
                    @forelse ($pendingAssignments as $assignment)
                        <li>
                            <a href="{{ route('student.assignments.show', $assignment) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                <span class="grid size-9 place-items-center rounded-lg bg-violet-50 text-violet-600"><x-lucide-notebook-pen class="size-4" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-navy">{{ $assignment->title }}</span>
                                    <span class="block text-xs text-muted">{{ $assignment->batch->name }}</span>
                                </span>
                                <span @class(['text-xs font-semibold', 'text-rose-600' => $assignment->due_at->diffInHours(now(), true) < 48, 'text-muted' => $assignment->due_at->diffInHours(now(), true) >= 48])>{{ $assignment->due_at->diffForHumans() }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="flex items-center gap-3 px-5 py-4 text-sm text-emerald-700"><x-lucide-circle-check class="size-5" />{{ __('You are all caught up.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Latest results') }}</h2><a href="{{ route('student.grades') }}" class="text-xs font-semibold text-brand">{{ __('All grades') }}</a></div>
                <ul class="space-y-4 p-5">
                    @forelse ($recentResults as $result)
                        <li>
                            <div class="flex justify-between text-sm"><span class="font-medium">{{ $result->exam->title }}</span><span class="font-bold tabular-nums">{{ (float) $result->score }}/{{ $result->exam->max_score }}</span></div>
                            <div class="mt-1.5 h-2 rounded-full bg-line"><div @class(['h-2 rounded-full', 'bg-emerald-500' => $result->passed(), 'bg-rose-500' => ! $result->passed()]) style="width: {{ $result->percent() }}%"></div></div>
                        </li>
                    @empty
                        <li class="text-sm text-muted">{{ __('No published results yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Today') }}</h2></div>
                <ol class="space-y-3 p-5">
                    @forelse ($todayClasses as $slot)
                        <li class="flex gap-3 rounded-xl border-s-4 border-brand bg-canvas p-3">
                            <span class="w-24 shrink-0 font-latin text-xs font-semibold text-muted" dir="ltr">{{ $slot->timeRange() }}</span>
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-navy">{{ $slot->batch->course->tr('title') }}</span><span class="block text-xs text-muted">{{ $slot->batch->room }}</span></span>
                        </li>
                    @empty
                        <li class="text-center text-sm text-muted">{{ __('No classes today.') }}</li>
                    @endforelse
                </ol>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Upcoming exams') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($upcomingExams as $exam)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid w-12 shrink-0 rounded-lg bg-amber-50 py-1 text-center text-amber-700">
                                <span class="text-[10px]">{{ $exam->exam_date->translatedFormat('M') }}</span>
                                <span class="text-lg font-bold leading-none">{{ $exam->exam_date->format('d') }}</span>
                            </span>
                            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-navy">{{ $exam->title }}</span><span class="block truncate text-xs text-muted">{{ $exam->batch->name }}</span></span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">{{ __('No upcoming exams.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="space-y-3">
                @foreach ($announcements as $announcement)
                    @include('portal._announcement', ['announcement' => $announcement])
                @endforeach
            </div>
        </div>
    </div>
@endsection
