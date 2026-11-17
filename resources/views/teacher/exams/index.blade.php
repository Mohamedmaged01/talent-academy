@extends('layouts.portal')

@section('title', __('Exams & Grades'))

@section('content')
    <x-page-header :title="__('Exams & Grades')" :subtitle="__('Schedule exams, enter results and publish them to students.')">
        <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Schedule Exam') }}</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Exam') }}</th><th>{{ __('Class') }}</th><th>{{ __('Date') }}</th><th>{{ __('Results') }}</th><th>{{ __('Average') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('teacher.exams.show', $exam) }}'">
                            <td><p class="font-semibold text-navy">{{ $exam->title }}</p><p class="text-xs text-muted">{{ __(ucfirst($exam->type)) }} · {{ __('out of') }} {{ $exam->max_score }}</p></td>
                            <td class="text-muted">{{ $exam->batch->name }}</td>
                            <td class="text-xs text-muted">{{ $exam->exam_date->translatedFormat('j M Y') }}</td>
                            <td class="tabular-nums">{{ $exam->results_count }}</td>
                            <td class="tabular-nums">{{ $exam->results_avg_score !== null ? round($exam->results_avg_score, 1) : '—' }}</td>
                            <td>@if ($exam->results_published)<x-status value="published" />@elseif ($exam->exam_date->isFuture())<x-status value="upcoming" />@else<x-status value="draft" />@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="file-check" :title="__('No exams yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $exams->links() }}
    </div>
@endsection
