@extends('layouts.portal')

@section('title', __('Dashboard'))

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    @endphp

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <section class="card relative overflow-hidden bg-gradient-to-l from-brand-50 via-white to-white p-6 md:p-8">
                <div class="absolute -top-10 end-10 size-40 rounded-full bg-gold/20 blur-2xl"></div>
                <div class="relative">
                    <p class="text-2xl font-extrabold text-navy md:text-3xl">{{ $greeting }}{{ is_rtl() ? '،' : ',' }} {{ auth()->user()->name }} 👋</p>
                    <p class="mt-2 text-muted">
                        {{ trans_choice('You have :count class today|You have :count classes today', $todayClasses->count()) }}
                        @if ($toGrade) · <span class="font-semibold text-brand">{{ trans_choice(':count submission to grade|:count submissions to grade', $toGrade) }}</span>@endif
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('teacher.batches.index') }}" class="btn btn-primary"><x-lucide-users-round class="size-4" />{{ __('My Classes') }}</a>
                        <a href="{{ route('teacher.assignments.create') }}" class="btn btn-outline"><x-lucide-notebook-pen class="size-4" />{{ __('New Assignment') }}</a>
                        <a href="{{ route('teacher.exams.create') }}" class="btn btn-outline"><x-lucide-file-check class="size-4" />{{ __('Schedule Exam') }}</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat :label="__('Classes today')" :value="$todayClasses->count()" icon="calendar-days" :href="route('teacher.schedule')" />
                <x-stat :label="__('My students')" :value="$studentCount" icon="users" tone="emerald" :hint="trans_choice('across :count class|across :count classes', $batches->count())" />
                <x-stat :label="__('To grade')" :value="$toGrade" icon="clipboard-pen" tone="violet" :href="route('teacher.assignments.index')" />
                <x-stat :label="__('Submission rate')" :value="$submissionRate.'%'" icon="chart-pie" tone="amber" />
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('My classes') }}</h2><a href="{{ route('teacher.batches.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a></div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    @forelse ($batches as $batch)
                        <div class="rounded-xl border border-line p-4">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('teacher.batches.show', $batch) }}" class="font-semibold text-navy hover:text-brand">{{ $batch->name }}</a>
                                <span class="text-xs text-muted">{{ $batch->enrollments_count }} {{ __('students') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted">{{ $batch->scheduleSummary() }}</p>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('teacher.attendance.edit', $batch) }}" class="btn btn-primary btn-sm"><x-lucide-clipboard-check class="size-4" />{{ __('Take attendance') }}</a>
                                <a href="{{ route('teacher.batches.show', $batch) }}" class="btn btn-outline btn-sm">{{ __('Open') }}</a>
                            </div>
                        </div>
                    @empty
                        <x-empty icon="users-round" :title="__('No classes assigned yet')" class="md:col-span-2" />
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Recent assignments') }}</h2><a href="{{ route('teacher.assignments.index') }}" class="text-xs font-semibold text-brand">{{ __('View all') }}</a></div>
                <ul class="divide-y divide-line">
                    @forelse ($assignments as $assignment)
                        <li>
                            <a href="{{ route('teacher.assignments.show', $assignment) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                <span class="grid size-9 place-items-center rounded-lg bg-violet-50 text-violet-600"><x-lucide-notebook-pen class="size-4" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-navy">{{ $assignment->title }}</span>
                                    <span class="block text-xs text-muted">{{ $assignment->batch->name }} · {{ __('Due') }} {{ $assignment->due_at->translatedFormat('j M H:i') }}</span>
                                </span>
                                <span class="text-xs text-muted">{{ $assignment->submissions_count }} {{ __('submitted') }}</span>
                            </a>
                        </li>
                    @empty
                        <li><x-empty icon="notebook-pen" :title="__('No assignments yet')" /></li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __("Today's Schedule") }}</h2><a href="{{ route('teacher.schedule') }}" class="text-xs font-semibold text-brand">{{ __('Week') }}</a></div>
                <ol class="space-y-3 p-5">
                    @forelse ($todayClasses as $slot)
                        @php $now = now()->format('H:i:s') >= $slot->starts_at && now()->format('H:i:s') <= $slot->ends_at; @endphp
                        <li @class(['flex items-center gap-3 rounded-xl border-s-4 p-3', 'border-brand bg-brand-50' => $now, 'border-line bg-canvas' => ! $now])>
                            <span class="w-24 shrink-0 font-latin text-xs font-semibold text-muted" dir="ltr">{{ $slot->timeRange() }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-navy">{{ $slot->batch->name }}</span>
                                <span class="block truncate text-xs text-muted">{{ $slot->batch->room }}</span>
                            </span>
                            @if ($now)<span class="badge bg-brand text-white">{{ __('Now') }}</span>@endif
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-muted">{{ __('No classes today. Enjoy your day!') }}</li>
                    @endforelse
                </ol>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Upcoming exams') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($upcomingExams as $exam)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid w-12 shrink-0 rounded-lg bg-amber-50 py-1 text-center text-amber-700">
                                <span class="text-[10px] uppercase">{{ $exam->exam_date->translatedFormat('M') }}</span>
                                <span class="text-lg font-bold leading-none">{{ $exam->exam_date->format('d') }}</span>
                            </span>
                            <a href="{{ route('teacher.exams.show', $exam) }}" class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-navy">{{ $exam->title }}</span>
                                <span class="block truncate text-xs text-muted">{{ $exam->batch->name }}</span>
                            </a>
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
