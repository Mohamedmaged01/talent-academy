@extends('layouts.portal')

@section('title', $question->exists ? __('Edit question') : __('New question'))

@section('content')
    <x-page-header :title="$question->exists ? __('Edit question') : __('New question')" :back="route($prefix.'.index')" />

    @php
        $options = old('options', $question->options ?: ['', '', '', '']);
        $options = array_pad($options, 4, '');
    @endphp

    <form method="POST" action="{{ $question->exists ? route($prefix.'.update', $question) : route($prefix.'.store') }}" class="card max-w-3xl"
          x-data="{ type: @js(old('type', $question->type)), options: @js(array_values($options)), answer: @js(old('correct_answer', $question->correct_answer)), correct: -1 }"
          x-init="correct = options.findIndex(o => o !== '' && o === answer)">
        @csrf
        @if ($question->exists) @method('PUT') @endif
        <div class="grid gap-4 p-6 sm:grid-cols-3">
            <x-select name="course_id" :label="__('Course')" required :value="$question->course_id" class="sm:col-span-3" :options="$courses->mapWithKeys(fn ($c) => [$c->id => $c->tr('title')])" :placeholder="__('Select…')" />
            <div>
                <label class="label" for="type">{{ __('Type') }}</label>
                <select id="type" name="type" class="input" x-model="type">
                    @foreach (\App\Models\Question::TYPES as $type)
                        <option value="{{ $type }}">{{ __('question.'.$type) }}</option>
                    @endforeach
                </select>
            </div>
            <x-input name="points" :label="__('Points')" type="number" min="1" max="100" :value="$question->points" required />
            <x-select name="difficulty" :label="__('Difficulty')" :value="$question->difficulty" required :options="collect(\App\Models\Question::DIFFICULTIES)->mapWithKeys(fn ($d) => [$d => __('difficulty.'.$d)])" />
            <x-input name="body" :label="__('Question')" type="textarea" rows="3" required :value="$question->body" class="sm:col-span-3" dir="auto" />

            <div class="sm:col-span-3" x-show="type === 'mcq'">
                <p class="label">{{ __('Options — pick the correct one') }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <template x-for="(option, i) in options" :key="i">
                        <label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2">
                            <input type="radio" name="mcq_correct" :value="i" :checked="i === correct" @change="correct = i" aria-label="{{ __('Correct') }}">
                            <input type="text" name="options[]" x-model="options[i]" class="w-full border-0 bg-transparent p-0 text-sm focus:ring-0 focus:outline-none" :placeholder="'{{ __('Option') }} ' + (i + 1)" dir="auto">
                        </label>
                    </template>
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" @click="options.push('')" x-show="options.length < 8"><x-lucide-plus class="size-4" />{{ __('Add option') }}</button>
                @error('options')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-3" x-show="type === 'true_false'" x-cloak>
                <p class="label">{{ __('Correct answer') }}</p>
                <div class="flex gap-3">
                    @foreach (['true', 'false'] as $tf)
                        <label class="flex items-center gap-2 rounded-xl border border-line px-4 py-2 text-sm">
                            <input type="radio" value="{{ $tf }}" x-model="answer"> {{ __('answer.'.$tf) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="sm:col-span-3" x-show="type === 'short_answer'" x-cloak>
                <label class="label" for="short_answer">{{ __('Expected answer') }}</label>
                <input id="short_answer" type="text" class="input" x-model="answer" dir="auto">
                <p class="hint">{{ __('A response is correct when it matches or contains this text (case is ignored).') }}</p>
            </div>

            <input type="hidden" name="correct_answer" :value="type === 'mcq' ? (options[correct] ?? '') : answer">
            @error('correct_answer')<p class="field-error sm:col-span-3">{{ $message }}</p>@enderror

            <x-input name="explanation" :label="__('Explanation shown after results are published')" type="textarea" rows="2" :value="$question->explanation" class="sm:col-span-3" dir="auto" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route($prefix.'.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
