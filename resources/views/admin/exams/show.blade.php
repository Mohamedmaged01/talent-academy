@extends('layouts.portal')

@section('title', $exam->title)

@section('content')
    <x-page-header :title="$exam->title" :subtitle="$exam->batch->name.' · '.$exam->batch->teacher?->name.' · '.$exam->exam_date->translatedFormat('j F Y')" :back="route('admin.exams.index')">
        @if (auth()->user()->canAdmin('exams', 'manage'))
            <form method="POST" action="{{ route('admin.exams.publish', $exam) }}">
                @csrf
                <button @class(['btn', 'btn-primary' => ! $exam->results_published, 'btn-outline' => $exam->results_published])>
                    @if ($exam->results_published)<x-lucide-eye-off class="size-4" />{{ __('Hide results') }}@else<x-lucide-eye class="size-4" />{{ __('Publish results') }}@endif
                </button>
            </form>
        @endif
    </x-page-header>

    @php($scores = $results->pluck('score')->map(fn ($s) => (float) $s))
    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat :label="__('Results entered')" :value="$results->count().' / '.$students->count()" icon="list-checks" />
        <x-stat :label="__('Average')" :value="$scores->isNotEmpty() ? round($scores->avg(), 1).' / '.$exam->max_score : '—'" icon="chart-column" tone="violet" />
        <x-stat :label="__('Passed')" :value="$scores->filter(fn ($s) => $s >= $exam->pass_score)->count()" icon="circle-check" tone="emerald" :hint="__('Pass mark: :mark', ['mark' => $exam->pass_score])" />
        <x-stat :label="__('Mode')" :value="$exam->isOnline() ? __('Online') : __('Paper')" :icon="$exam->isOnline() ? 'monitor' : 'file-text'" tone="amber"
                :hint="$exam->isOnline() ? trans_choice(':count question|:count questions', $exam->questions->count(), ['count' => $exam->questions->count()]) : null" />
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">{{ __('Results') }}</h2><x-status :value="$exam->results_published ? 'published' : 'draft'" /></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>{{ __('Student') }}</th><th>{{ __('Score') }}</th><th>{{ __('Result') }}</th>@if ($exam->isOnline())<th>{{ __('Attempt') }}</th>@endif<th>{{ __('Remarks') }}</th></tr></thead>
                <tbody>
                    @forelse ($students as $i => $student)
                        @php($result = $results[$student->id] ?? null)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td><a href="{{ route('admin.students.show', $student) }}" class="font-semibold text-navy hover:text-brand">{{ $student->full_name }}</a></td>
                            <td class="tabular-nums">{{ $result ? (float) $result->score.' / '.$exam->max_score : '—' }}</td>
                            <td>@if ($result)<x-status :value="$result->score >= $exam->pass_score ? 'passed' : 'failed'" />@endif</td>
                            @if ($exam->isOnline())
                                <td class="text-xs text-muted">
                                    @if ($attempt = $attempts[$student->id] ?? null)
                                        <x-status :value="$attempt->status" /> {{ $attempt->submitted_at?->translatedFormat('j M H:i') }}
                                    @else — @endif
                                </td>
                            @endif
                            <td class="text-xs text-muted">{{ $result?->remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="users" :title="__('No students enrolled yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
