@extends('layouts.portal')

@section('title', $exam->title)

@section('content')
    <x-page-header :title="$exam->title" :subtitle="$exam->batch->name.' · '.$exam->exam_date->translatedFormat('j F Y').' · '.__('out of').' '.$exam->max_score" :back="route('teacher.exams.index')">
        <x-confirm-delete :action="route('teacher.exams.destroy', $exam)" :label="__('Delete')" :message="__('Delete this exam and its results?')" />
    </x-page-header>

    @php
        $scores = $results->pluck('score')->map(fn ($s) => (float) $s);
    @endphp
    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat :label="__('Results entered')" :value="$results->count().' / '.$students->count()" icon="list-checks" />
        <x-stat :label="__('Average')" :value="$scores->isNotEmpty() ? round($scores->avg(), 1) : '—'" icon="chart-column" tone="violet" />
        <x-stat :label="__('Highest')" :value="$scores->isNotEmpty() ? $scores->max() : '—'" icon="trophy" tone="amber" />
        <x-stat :label="__('Passed')" :value="$scores->filter(fn ($s) => $s >= $exam->pass_score)->count()" icon="circle-check" tone="emerald" :hint="__('Pass mark: :mark', ['mark' => $exam->pass_score])" />
    </div>

    @if ($exam->isOnline())
        <div class="mb-6 grid gap-6 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ __('Exam questions') }}</h2>
                        <p class="text-xs text-muted">
                            {{ __('Open :from — closes :to', ['from' => $exam->opensAt()->translatedFormat('j M, H:i'), 'to' => $exam->closesAt()->translatedFormat('j M, H:i')]) }}
                            · {{ __(':count min per student', ['count' => $exam->duration_minutes]) }}
                        </p>
                    </div>
                    <span @class(['badge', 'bg-emerald-50 text-emerald-700' => $exam->isOpen(), 'bg-slate-100 text-slate-600' => ! $exam->isOpen()])>
                        {{ $exam->isOpen() ? __('Open now') : ($exam->hasClosed() ? __('Closed') : __('Not open yet')) }}
                    </span>
                </div>
                <ol class="divide-y divide-line">
                    @forelse ($exam->questions as $question)
                        <li class="flex items-start gap-3 px-5 py-3 text-sm">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand-50 text-xs font-bold text-brand">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-navy">{{ $question->body }}</p>
                                <p class="text-xs text-muted">{{ __('question.'.$question->type) }} · {{ trans_choice(':count point|:count points', $question->points, ['count' => $question->points]) }}</p>
                            </div>
                            @if ($attempts->isEmpty())
                                <form method="POST" action="{{ route('teacher.exams.questions.detach', [$exam, $question]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" title="{{ __('Remove') }}"><x-lucide-x class="size-4" /></button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li><x-empty icon="circle-help" :title="__('No questions yet')" :message="__('Students cannot start the exam until it has questions.')" /></li>
                    @endforelse
                </ol>
            </div>

            <div class="card h-fit">
                <div class="card-header"><h2 class="card-title">{{ __('Add from the question bank') }}</h2></div>
                @if ($attempts->isNotEmpty())
                    <p class="p-5 text-sm text-muted">{{ __('Students have started this exam, so its questions are locked.') }}</p>
                @elseif ($bank->isEmpty())
                    <div class="p-5 text-sm text-muted">
                        <p>{{ __('No unused questions for this course.') }}</p>
                        <a href="{{ route('teacher.questions.create', ['course' => $exam->batch->course_id]) }}" class="btn btn-outline btn-sm mt-3"><x-lucide-plus class="size-4" />{{ __('New question') }}</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('teacher.exams.questions.attach', $exam) }}" x-data="{ all: false }">
                        @csrf
                        <label class="flex items-center gap-2 border-b border-line px-5 py-2 text-xs text-muted">
                            <input type="checkbox" x-model="all" @change="$root.querySelectorAll('input[name=\'questions[]\']').forEach(c => c.checked = all)"> {{ __('Select all') }}
                        </label>
                        <ul class="max-h-96 divide-y divide-line overflow-y-auto">
                            @foreach ($bank as $question)
                                <li>
                                    <label class="flex cursor-pointer items-start gap-2 px-5 py-2.5 text-sm hover:bg-canvas">
                                        <input type="checkbox" name="questions[]" value="{{ $question->id }}" class="mt-1">
                                        <span class="min-w-0"><span class="line-clamp-2">{{ $question->body }}</span><span class="text-xs text-muted">{{ __('question.'.$question->type) }} · {{ $question->points }}</span></span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <div class="border-t border-line p-4"><button class="btn btn-primary w-full"><x-lucide-plus class="size-4" />{{ __('Add selected') }}</button></div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.exams.results', $exam) }}" class="card">
        @csrf @method('PUT')
        <div class="card-header">
            <h2 class="card-title">{{ __('Enter results') }}</h2>
            @if ($exam->results_published)<x-status value="published" />@else<x-status value="draft" />@endif
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>{{ __('Student') }}</th><th class="w-40">{{ __('Score') }}</th><th>{{ __('Remarks') }}</th></tr></thead>
                <tbody>
                    @forelse ($students as $i => $student)
                        @php $result = $results[$student->id] ?? null; @endphp
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="font-semibold text-navy">
                                {{ $student->full_name }}
                                @if ($exam->isOnline() && ($attempt = $attempts[$student->id] ?? null))
                                    <span class="block text-xs font-normal text-muted"><x-status :value="$attempt->status" /> {{ $attempt->submitted_at?->translatedFormat('j M H:i') }}</span>
                                @endif
                            </td>
                            <td>
                                <input type="number" name="scores[{{ $student->id }}]" step="0.5" min="0" max="{{ $exam->max_score }}"
                                       value="{{ old("scores.{$student->id}", $result ? (float) $result->score : '') }}" class="input py-1.5 tabular-nums" aria-label="{{ __('Score') }}">
                                @error("scores.{$student->id}")<p class="field-error">{{ $message }}</p>@enderror
                            </td>
                            <td><input type="text" name="remarks[{{ $student->id }}]" value="{{ old("remarks.{$student->id}", $result?->remarks) }}" class="input py-1.5" aria-label="{{ __('Remarks') }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="users" :title="__('No students enrolled yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="publish" value="0">
                <input type="checkbox" name="publish" value="1" @checked($exam->results_published)> {{ __('Publish results to students and parents') }}
            </label>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save results') }}</button>
        </div>
    </form>
@endsection
