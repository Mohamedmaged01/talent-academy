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
                            <td class="font-semibold text-navy">{{ $student->full_name }}</td>
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
