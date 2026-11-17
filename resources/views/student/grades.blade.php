@extends('layouts.portal')

@section('title', __('Grades'))

@section('content')
    <x-page-header :title="__('My Grades')" :subtitle="__('Overall exam average: :avg', ['avg' => pct($average)])" />

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Exam results') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Exam') }}</th><th>{{ __('Date') }}</th><th>{{ __('Score') }}</th><th>{{ __('Result') }}</th></tr></thead>
                    <tbody>
                        @forelse ($results as $result)
                            <tr>
                                <td><p class="font-semibold text-navy">{{ $result->exam->title }}</p><p class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($result->exam->batch->course->tr('title'), 40) }}</p></td>
                                <td class="text-xs text-muted">{{ $result->exam->exam_date->translatedFormat('j M Y') }}</td>
                                <td class="font-semibold tabular-nums">{{ (float) $result->score }}/{{ $result->exam->max_score }} <span class="text-xs text-muted">({{ pct($result->percent()) }})</span></td>
                                <td><x-status :value="$result->passed() ? 'passed' : 'failed'" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="chart-column" :title="__('No published results yet.')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Graded assignments') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($submissions as $submission)
                    <li class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('student.assignments.show', $submission->assignment) }}" class="font-semibold text-navy hover:text-brand">{{ $submission->assignment->title }}</a>
                            <span class="font-bold tabular-nums">{{ (float) $submission->score }}/{{ $submission->assignment->max_score }}</span>
                        </div>
                        @if ($submission->feedback)<p class="mt-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit($submission->feedback, 120) }}</p>@endif
                    </li>
                @empty
                    <li><x-empty icon="notebook-pen" :title="__('No graded assignments yet')" /></li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
