@extends('layouts.portal')

@section('title', $batch->name)

@section('content')
    <x-page-header :title="$batch->name" :subtitle="$batch->course->tr('title')" :back="route('admin.batches.index')">
        <a href="{{ route('admin.batches.edit', $batch) }}" class="btn btn-primary"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat :label="__('Enrolled')" :value="$students->where('pivot.status', 'active')->count().' / '.$batch->capacity" icon="users-round" />
        <x-stat :label="__('Instructor')" :value="$batch->teacher?->name ?? '—'" icon="presentation" tone="emerald" />
        <x-stat :label="__('Exams')" :value="$batch->exams->count()" icon="file-check" tone="violet" />
        <x-stat :label="__('Assignments')" :value="$batch->assignments->count()" icon="notebook-pen" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2><x-status :value="$batch->status" /></div>
                <dl class="divide-y divide-line text-sm">
                    @foreach ([
                        __('Code') => $batch->code,
                        __('Branch') => $batch->branch->tr('name'),
                        __('Room') => $batch->room,
                        __('Start date') => $batch->start_date?->translatedFormat('j F Y'),
                        __('End date') => $batch->end_date?->translatedFormat('j F Y'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-3 px-5 py-2.5"><dt class="text-muted">{{ $label }}</dt><dd class="text-end font-medium">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                <div class="border-t border-line px-5 py-4">
                    <p class="mb-2 text-xs font-semibold text-muted">{{ __('Weekly schedule') }}</p>
                    <ul class="space-y-1.5 text-sm">
                        @forelse ($batch->schedules as $slot)
                            <li class="flex justify-between"><span>{{ $slot->dayName() }}</span><span class="font-latin text-muted" dir="ltr">{{ $slot->timeRange() }}</span></li>
                        @empty
                            <li class="text-muted">{{ __('No sessions yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Enroll students') }}</h2></div>
                @if ($candidates->isEmpty())
                    <p class="p-5 text-sm text-muted">{{ __('All active students are already enrolled.') }}</p>
                @else
                    <form method="POST" action="{{ route('admin.batches.enroll', $batch) }}" x-data="{ q: '' }">
                        @csrf
                        <div class="p-4"><input type="search" x-model="q" placeholder="{{ __('Filter students…') }}" class="input"></div>
                        <div class="max-h-72 overflow-y-auto px-4">
                            @foreach ($candidates as $candidate)
                                <label class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm hover:bg-canvas"
                                       x-show="!q || @js(mb_strtolower($candidate->full_name.' '.$candidate->code)).includes(q.toLowerCase())">
                                    <input type="checkbox" name="student_ids[]" value="{{ $candidate->id }}">
                                    <span class="flex-1">{{ $candidate->full_name }}</span>
                                    <x-status :value="$candidate->pathway" />
                                </label>
                            @endforeach
                        </div>
                        <div class="border-t border-line p-4"><button class="btn btn-primary w-full"><x-lucide-user-plus class="size-4" />{{ __('Enroll selected') }}</button></div>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ __('Roster & certificate eligibility') }}</h2>
                        <p class="text-xs text-muted">{{ __('Eligible = attendance ≥ :a% and exam average ≥ :b%', ['a' => \App\Services\CertificateService::MIN_ATTENDANCE, 'b' => \App\Services\CertificateService::MIN_AVERAGE]) }}</p>
                    </div>
                    @if ($eligibility->contains(fn ($r) => $r['eligible'] && ! $r['certificate']))
                        <form method="POST" action="{{ route('admin.certificates.issue-batch', $batch) }}" onsubmit="return confirm(@js(__('Issue certificates to all eligible students?')))">
                            @csrf
                            <button class="btn btn-gold btn-sm"><x-lucide-award class="size-4" />{{ __('Issue certificates') }}</button>
                        </form>
                    @endif
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th><th>{{ __('Certificate') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($eligibility as $row)
                                <tr>
                                    <td><a href="{{ route('admin.students.show', $row['student']) }}" class="font-semibold text-navy hover:text-brand">{{ $row['student']->full_name }}</a></td>
                                    <td @class(['tabular-nums', 'text-rose-600 font-semibold' => $row['attendance'] !== null && $row['attendance'] < \App\Services\CertificateService::MIN_ATTENDANCE])>{{ pct($row['attendance']) }}</td>
                                    <td @class(['tabular-nums', 'text-rose-600 font-semibold' => $row['average'] !== null && $row['average'] < \App\Services\CertificateService::MIN_AVERAGE])>{{ pct($row['average']) }}</td>
                                    <td>
                                        @if ($row['certificate'])
                                            <a href="{{ route('admin.certificates.show', $row['certificate']) }}" class="badge bg-emerald-50 text-emerald-700"><x-lucide-award class="size-3" />{{ __('Issued') }}</a>
                                        @elseif ($row['eligible'])
                                            <x-status value="eligible" />
                                        @else
                                            <span class="text-xs text-muted">{{ __('Not yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><x-confirm-delete :action="route('admin.batches.unenroll', [$batch, $row['student']])" icon="user-minus" :message="__('Remove this student from the class?')" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty icon="users-round" :title="__('No students enrolled yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Exams') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($batch->exams as $exam)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span><span class="block font-medium">{{ $exam->title }}</span><span class="text-xs text-muted">{{ $exam->exam_date->translatedFormat('j M Y') }} · {{ __(ucfirst($exam->type)) }}</span></span>
                                <span class="text-xs text-muted">{{ $exam->results_count }} {{ __('results') }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-4 text-muted">{{ __('No exams yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Assignments') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @forelse ($batch->assignments as $assignment)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span><span class="block font-medium">{{ $assignment->title }}</span><span class="text-xs text-muted">{{ __('Due') }} {{ $assignment->due_at->translatedFormat('j M Y') }}</span></span>
                                <span class="text-xs text-muted">{{ $assignment->submissions_count }} {{ __('submitted') }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-4 text-muted">{{ __('No assignments yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <x-confirm-delete :action="route('admin.batches.destroy', $batch)" :label="__('Delete class')" :message="__('Classes with students are cancelled instead of deleted. Continue?')" />
    </div>
@endsection
