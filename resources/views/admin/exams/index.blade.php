@extends('layouts.portal')

@section('title', __('Exams'))

@section('content')
    <x-page-header :title="__('Exams')" :subtitle="__('Every paper and online exam across the academy. Teachers schedule them from their portal.')">
        <a href="{{ route('admin.questions.index') }}" class="btn btn-outline"><x-lucide-circle-help class="size-4" />{{ __('Question Bank') }}</a>
        <a href="{{ route('admin.lectures.index') }}" class="btn btn-outline"><x-lucide-clapperboard class="size-4" />{{ __('Recorded Lectures') }}</a>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Upcoming exams')" :value="$counts['upcoming']" icon="calendar-clock" />
        <x-stat :label="__('Online exams')" :value="$counts['online']" icon="monitor-check" tone="violet" />
        <x-stat :label="__('Past exams with unpublished results')" :value="$counts['unpublished']" icon="eye-off" tone="amber" :href="route('admin.exams.index', ['status' => 'draft'])" />
    </div>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-3">
            <select name="batch" class="input" onchange="this.form.submit()" aria-label="{{ __('Class') }}">
                <option value="">{{ __('All classes') }}</option>
                @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((string) request('batch') === (string) $batch->id)>{{ $batch->name }}</option>
                @endforeach
            </select>
            <select name="mode" class="input" onchange="this.form.submit()" aria-label="{{ __('Mode') }}">
                <option value="">{{ __('Paper and online') }}</option>
                <option value="paper" @selected(request('mode') === 'paper')>{{ __('On paper / in class') }}</option>
                <option value="online" @selected(request('mode') === 'online')>{{ __('Online in the portal') }}</option>
            </select>
            <select name="status" class="input" onchange="this.form.submit()" aria-label="{{ __('Status') }}">
                <option value="">{{ __('Any status') }}</option>
                <option value="published" @selected(request('status') === 'published')>{{ __('Published') }}</option>
                <option value="draft" @selected(request('status') === 'draft')>{{ __('Draft') }}</option>
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Exam') }}</th><th>{{ __('Class') }}</th><th>{{ __('Date') }}</th><th>{{ __('Mode') }}</th><th>{{ __('Results') }}</th><th>{{ __('Average') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td><a href="{{ route('admin.exams.show', $exam) }}" class="font-semibold text-navy hover:text-brand">{{ $exam->title }}</a><p class="text-xs text-muted">{{ __(ucfirst($exam->type)) }}</p></td>
                            <td><p>{{ $exam->batch->name }}</p><p class="text-xs text-muted">{{ $exam->batch->teacher?->name }}</p></td>
                            <td class="text-xs text-muted whitespace-nowrap">{{ $exam->exam_date->translatedFormat('j M Y') }}</td>
                            <td>
                                @if ($exam->isOnline())
                                    <span class="badge bg-violet-50 text-violet-700"><x-lucide-monitor class="size-3" />{{ __('Online') }}</span>
                                    <p class="text-xs text-muted">{{ trans_choice(':count question|:count questions', $exam->questions_count, ['count' => $exam->questions_count]) }}</p>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600">{{ __('Paper') }}</span>
                                @endif
                            </td>
                            <td class="tabular-nums">{{ $exam->results_count }}</td>
                            <td class="tabular-nums">{{ $exam->results_avg_score !== null ? round($exam->results_avg_score, 1).' / '.$exam->max_score : '—' }}</td>
                            <td><x-status :value="$exam->results_published ? 'published' : 'draft'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="file-check" :title="__('No exams yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $exams->links() }}
    </div>
@endsection
