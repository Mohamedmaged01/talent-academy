@extends('layouts.portal')

@section('title', __('Schedule Exam'))

@section('content')
    <x-page-header :title="__('Schedule Exam')" :back="route('teacher.exams.index')" />

    <form method="POST" action="{{ route('teacher.exams.store') }}" class="card max-w-3xl" x-data="{ mode: @js(old('mode', 'paper')) }">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="batch_id" :label="__('Class')" :value="$selected" required class="sm:col-span-2" :options="$batches->pluck('name', 'id')" :placeholder="__('Select…')" />
            <x-input name="title" :label="__('Title')" required class="sm:col-span-2" />

            <fieldset class="sm:col-span-2">
                <legend class="label">{{ __('How is it taken?') }}</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 has-[:checked]:border-brand has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="mode" value="paper" x-model="mode" class="mt-1">
                        <span><span class="block font-semibold text-navy">{{ __('On paper / in class') }}</span><span class="text-xs text-muted">{{ __('You enter each student\'s score afterwards.') }}</span></span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 has-[:checked]:border-brand has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="mode" value="online" x-model="mode" class="mt-1">
                        <span><span class="block font-semibold text-navy">{{ __('Online in the portal') }}</span><span class="text-xs text-muted">{{ __('Students answer bank questions within a time limit and it grades itself.') }}</span></span>
                    </label>
                </div>
            </fieldset>

            <x-select name="type" :label="__('Type')" required :options="collect(\App\Models\Exam::TYPES)->mapWithKeys(fn ($t) => [$t => __(ucfirst($t))])" />
            <x-input name="exam_date" :label="__('Date')" type="date" :value="now()->addWeek()->toDateString()" required />
            <x-input name="starts_at" :label="__('Start time')" type="time" value="10:00" />
            <x-input name="duration_minutes" :label="__('Duration (minutes)')" type="number" min="5" value="60" required />

            <template x-if="mode === 'online'">
                <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <x-input name="closes_at" :label="__('Closes at (optional)')" type="datetime-local" :hint="__('Students can start any time between the start and the closing time. Without one it closes at the end of the exam day.')" />
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="publish_immediately" value="1" @checked(old('publish_immediately'))> {{ __('Show each student their score as soon as they submit') }}
                        </label>
                    </div>
                    <x-input name="instructions" :label="__('Instructions for students')" type="textarea" rows="3" class="sm:col-span-2" dir="auto" />
                </div>
            </template>

            <x-input name="max_score" :label="__('Maximum score')" type="number" min="1" value="100" required :hint="__('For online exams this becomes the total of the question points.')" />
            <x-input name="pass_score" :label="__('Pass mark')" type="number" min="0" value="50" required />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
