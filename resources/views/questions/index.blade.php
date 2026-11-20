@extends('layouts.portal')

@section('title', __('Question Bank'))

@section('content')
    <x-page-header :title="__('Question Bank')" :subtitle="__('Reusable questions per course for online exams. Multiple choice, true/false and short answers are graded automatically.')">
        <a href="{{ route($prefix.'.create', ['course' => request('course')]) }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('New question') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-3">
                <select name="course" class="input" onchange="this.form.submit()" aria-label="{{ __('Course') }}">
                    <option value="">{{ __('All courses') }}</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) request('course') === (string) $course->id)>{{ $course->tr('title') }}</option>
                    @endforeach
                </select>
                <select name="type" class="input" onchange="this.form.submit()" aria-label="{{ __('Type') }}">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (\App\Models\Question::TYPES as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('question.'.$type) }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Search questions…') }}">
            </form>

            <ul class="divide-y divide-line">
                @forelse ($questions as $question)
                    <li class="px-5 py-4">
                        <div class="flex flex-wrap items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-navy">{{ $question->body }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="badge bg-brand-50 text-brand">{{ __('question.'.$question->type) }}</span>
                                    <span class="badge bg-slate-100 text-slate-600">{{ __('difficulty.'.$question->difficulty) }}</span>
                                    <span class="text-muted">{{ trans_choice(':count point|:count points', $question->points, ['count' => $question->points]) }}</span>
                                    <span class="text-muted">· {{ $question->course->tr('title') }}</span>
                                    @if ($question->exams_count)<span class="text-muted">· {{ trans_choice('Used in :count exam|Used in :count exams', $question->exams_count, ['count' => $question->exams_count]) }}</span>@endif
                                </div>
                                @if ($question->type === 'mcq')
                                    <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                                        @foreach ($question->options as $option)
                                            <li @class(['flex items-center gap-1.5', 'font-semibold text-emerald-700' => \App\Models\Question::normalize($option) === \App\Models\Question::normalize($question->correct_answer), 'text-muted' => \App\Models\Question::normalize($option) !== \App\Models\Question::normalize($question->correct_answer)])>
                                                <x-lucide-circle-dot class="size-3.5 shrink-0" />{{ $option }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-2 text-sm"><span class="text-muted">{{ __('Answer') }}:</span> <span class="font-semibold text-emerald-700">{{ $question->type === 'true_false' ? __('answer.'.$question->correct_answer) : $question->correct_answer }}</span></p>
                                @endif
                            </div>
                            @if ($canEdit($question))
                                <div class="flex items-center gap-1">
                                    <a href="{{ route($prefix.'.edit', $question) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                                    <x-confirm-delete :action="route($prefix.'.destroy', $question)" />
                                </div>
                            @endif
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="circle-help" :title="__('No questions yet')" :message="__('Add questions one by one or import a list.')" /></li>
                @endforelse
            </ul>
            {{ $questions->links() }}
        </div>

        <form method="POST" action="{{ route($prefix.'.import') }}" enctype="multipart/form-data" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Import questions') }}</h2></div>
            <div class="grid gap-4 p-5">
                <x-select name="course_id" :label="__('Course')" required :value="request('course')" :options="$courses->mapWithKeys(fn ($c) => [$c->id => $c->tr('title')])" :placeholder="__('Select…')" />
                <div class="rounded-xl bg-canvas p-3 text-xs leading-6 text-muted">
                    <p class="font-semibold text-ink">{{ __('One question per line:') }}</p>
                    <code class="block font-latin" dir="ltr">Question | A; B; C; D | correct | points</code>
                    <code class="block font-latin" dir="ltr">The sky is blue | | true | 1</code>
                    <code class="block font-latin" dir="ltr">Capital of Egypt? | | Cairo | 2</code>
                </div>
                <x-input name="text" :label="__('Paste questions')" type="textarea" rows="6" dir="auto" />
                <div>
                    <label class="label" for="file">{{ __('…or upload a .txt / .csv file') }}</label>
                    <input id="file" type="file" name="file" accept=".txt,.csv" class="input">
                    @error('file')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-outline"><x-lucide-upload class="size-4" />{{ __('Import') }}</button>
            </div>
        </form>
    </div>
@endsection
