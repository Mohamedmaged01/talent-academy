@extends('layouts.portal')

@section('title', __('Online Exams'))

@section('content')
    <x-page-header :title="__('Online Exams')" :subtitle="__('Timed quizzes and exams you take in the portal.')" />

    @php
        $sections = [
            [__('Open now'), $open, 'circle-play', __('Nothing open right now.')],
            [__('Coming up'), $upcoming, 'calendar-clock', __('No upcoming online exams.')],
            [__('Finished'), $past, 'history', __('No finished exams yet.')],
        ];
    @endphp

    <div class="space-y-6">
        @foreach ($sections as [$title, $rows, $icon, $emptyText])
            <div class="card">
                <div class="card-header"><h2 class="card-title flex items-center gap-2"><x-dynamic-component :component="'lucide-'.$icon" class="size-5 text-brand" />{{ $title }}</h2><span class="text-sm text-muted">{{ $rows->count() }}</span></div>
                <ul class="divide-y divide-line">
                    @forelse ($rows as $row)
                        @php $exam = $row['exam']; @endphp
                        <li>
                            <a href="{{ route('student.quizzes.show', $exam) }}" class="flex flex-wrap items-center gap-3 px-5 py-4 hover:bg-brand-50/40">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-navy">{{ $exam->title }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $exam->batch->course->tr('title') }} ·
                                        {{ trans_choice(':count question|:count questions', $exam->questions_count, ['count' => $exam->questions_count]) }} ·
                                        {{ __(':count min', ['count' => $exam->duration_minutes]) }}
                                    </p>
                                </div>
                                <div class="text-end text-xs text-muted">
                                    @if ($row['state'] === 'upcoming')
                                        {{ __('Opens :date', ['date' => $exam->opensAt()->translatedFormat('j M, H:i')]) }}
                                    @elseif (in_array($row['state'], ['open', 'in_progress'], true))
                                        {{ __('Closes :date', ['date' => $exam->closesAt()->translatedFormat('j M, H:i')]) }}
                                    @elseif ($row['attempt'] && $exam->results_published)
                                        <span class="text-base font-bold text-navy tabular-nums">{{ (float) $row['attempt']->score }} / {{ $exam->max_score }}</span>
                                    @endif
                                </div>
                                <x-status :value="$row['state']" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">{{ $emptyText }}</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>
@endsection
