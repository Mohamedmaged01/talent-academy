@extends('layouts.portal')

@section('title', $exam->title)

@section('content')
    <x-page-header :title="$exam->title" :subtitle="$exam->batch->course->tr('title').' · '.$exam->batch->name" :back="route('student.quizzes.index')" />

    @if ($state === 'in_progress')
        {{-- Taking the exam --}}
        @php $deadline = $attempt->deadline(); @endphp
        <form method="POST" action="{{ route('student.quizzes.submit', $exam) }}" id="quiz-form" class="space-y-4"
              x-data="{
                  left: Math.max(0, Math.floor(({{ $deadline->getTimestamp() }} * 1000 - Date.now()) / 1000)),
                  submitting: false,
                  get clock() { const m = Math.floor(this.left / 60), s = this.left % 60; return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'); },
                  tick() { if (this.left > 0) { this.left--; } else if (! this.submitting) { this.submitting = true; document.getElementById('quiz-form').submit(); } }
              }"
              x-init="setInterval(() => tick(), 1000)" @submit="submitting = true">
            @csrf
            <div class="card sticky top-[4.5rem] z-20 flex items-center justify-between gap-3 px-5 py-3">
                <p class="text-sm text-muted">{{ trans_choice(':count question|:count questions', $exam->questions->count(), ['count' => $exam->questions->count()]) }} · {{ __('out of') }} {{ $exam->max_score }}</p>
                <p class="flex items-center gap-2 font-latin text-xl font-extrabold tabular-nums" :class="left < 60 ? 'text-rose-600' : 'text-navy'" dir="ltr" role="timer" aria-live="off">
                    <x-lucide-timer class="size-5" /><span x-text="clock">--:--</span>
                </p>
            </div>

            @if ($exam->instructions)
                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 whitespace-pre-line" dir="auto">{{ $exam->instructions }}</div>
            @endif

            @foreach ($exam->questions as $question)
                <fieldset class="card p-5">
                    <legend class="sr-only">{{ __('Question :n', ['n' => $loop->iteration]) }}</legend>
                    <div class="flex items-start gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand text-sm font-bold text-white">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold leading-7 text-navy" dir="auto">{{ $question->body }}</p>
                            <p class="text-xs text-muted">{{ trans_choice(':count point|:count points', $question->points, ['count' => $question->points]) }}</p>

                            @if ($question->type === 'short_answer')
                                <input type="text" name="answers[{{ $question->id }}]" class="input mt-3" maxlength="500" dir="auto" aria-label="{{ __('Your answer') }}" autocomplete="off">
                            @else
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ($question->choices() as $choice)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-50">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $choice }}">
                                            <span dir="auto">{{ $question->type === 'true_false' ? __('answer.'.$choice) : $choice }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </fieldset>
            @endforeach

            <div class="flex justify-end">
                <button class="btn btn-primary" onclick="return confirm(@js(__('Submit your answers? You cannot change them afterwards.')))" :disabled="submitting">
                    <x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Submit answers') }}
                </button>
            </div>
        </form>
    @elseif (in_array($state, ['submitted', 'expired'], true))
        {{-- Finished --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat :label="__('Status')" :value="__(\Illuminate\Support\Str::headline($state))" icon="circle-check" :tone="$state === 'expired' ? 'rose' : 'emerald'" />
            <x-stat :label="__('Submitted')" :value="$attempt->submitted_at?->translatedFormat('j M H:i') ?? '—'" icon="clock" />
            @if ($exam->results_published && $result)
                <x-stat :label="__('Your score')" :value="(float) $result->score.' / '.$exam->max_score" icon="trophy" :tone="$result->passed() ? 'emerald' : 'rose'" :hint="$result->passed() ? __('Passed') : __('Below the pass mark')" />
            @else
                <x-stat :label="__('Your score')" :value="__('Pending')" icon="hourglass" tone="amber" :hint="__('Your teacher will publish the results.')" />
            @endif
        </div>

        @if ($exam->results_published)
            <div class="card mt-6">
                <div class="card-header"><h2 class="card-title">{{ __('Review your answers') }}</h2></div>
                <ol class="divide-y divide-line">
                    @foreach ($exam->questions as $question)
                        @php
                            $given = $attempt->answers[$question->id] ?? null;
                            $correct = $question->isCorrect($given);
                        @endphp
                        <li class="flex items-start gap-3 px-5 py-4 text-sm">
                            @if ($correct)
                                <x-lucide-circle-check class="mt-0.5 size-5 shrink-0 text-emerald-600" />
                            @else
                                <x-lucide-circle-x class="mt-0.5 size-5 shrink-0 text-rose-600" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-navy" dir="auto">{{ $loop->iteration }}. {{ $question->body }}</p>
                                <p class="mt-1"><span class="text-muted">{{ __('Your answer') }}:</span> <span dir="auto">{{ filled($given) ? ($question->type === 'true_false' ? __('answer.'.$given) : $given) : '—' }}</span></p>
                                @unless ($correct)
                                    <p><span class="text-muted">{{ __('Correct answer') }}:</span> <span class="font-semibold text-emerald-700" dir="auto">{{ $question->type === 'true_false' ? __('answer.'.$question->correct_answer) : $question->correct_answer }}</span></p>
                                @endunless
                                @if ($question->explanation)<p class="mt-1 text-xs text-muted" dir="auto">{{ $question->explanation }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    @else
        {{-- Before starting --}}
        <div class="card max-w-2xl p-6">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">{{ __('Opens') }}</dt><dd class="font-semibold text-navy">{{ $exam->opensAt()->translatedFormat('l j F, H:i') }}</dd></div>
                <div><dt class="text-muted">{{ __('Closes') }}</dt><dd class="font-semibold text-navy">{{ $exam->closesAt()->translatedFormat('l j F, H:i') }}</dd></div>
                <div><dt class="text-muted">{{ __('Time limit') }}</dt><dd class="font-semibold text-navy">{{ __(':count min', ['count' => $exam->duration_minutes]) }}</dd></div>
                <div><dt class="text-muted">{{ __('Questions') }}</dt><dd class="font-semibold text-navy">{{ $exam->questions->count() }} · {{ __('out of') }} {{ $exam->max_score }}</dd></div>
            </dl>
            @if ($exam->instructions)
                <p class="mt-5 rounded-xl bg-canvas p-4 text-sm leading-7 whitespace-pre-line" dir="auto">{{ $exam->instructions }}</p>
            @endif

            <div class="mt-6">
                @if ($state === 'open')
                    <form method="POST" action="{{ route('student.quizzes.start', $exam) }}" onsubmit="return confirm(@js(__('The timer starts now and cannot be paused. Start?')))">
                        @csrf
                        <button class="btn btn-primary"><x-lucide-circle-play class="size-4" />{{ __('Start exam') }}</button>
                    </form>
                    @error('exam')<p class="field-error">{{ $message }}</p>@enderror
                @elseif ($state === 'upcoming')
                    <p class="flex items-center gap-2 text-sm text-muted"><x-lucide-calendar-clock class="size-4" />{{ __('This exam is not open yet.') }}</p>
                @else
                    <p class="flex items-center gap-2 text-sm text-rose-600"><x-lucide-circle-x class="size-4" />{{ __('This exam has closed and you did not take it.') }}</p>
                @endif
            </div>
        </div>
    @endif
@endsection
