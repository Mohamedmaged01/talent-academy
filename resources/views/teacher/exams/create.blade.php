@extends('layouts.portal')

@section('title', __('Schedule Exam'))

@section('content')
    <x-page-header :title="__('Schedule Exam')" :back="route('teacher.exams.index')" />

    <form method="POST" action="{{ route('teacher.exams.store') }}" class="card max-w-3xl">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="batch_id" :label="__('Class')" :value="$selected" required class="sm:col-span-2" :options="$batches->pluck('name', 'id')" :placeholder="__('Select…')" />
            <x-input name="title" :label="__('Title')" required class="sm:col-span-2" />
            <x-select name="type" :label="__('Type')" required :options="collect(\App\Models\Exam::TYPES)->mapWithKeys(fn ($t) => [$t => __(ucfirst($t))])" />
            <x-input name="exam_date" :label="__('Date')" type="date" :value="now()->addWeek()->toDateString()" required />
            <x-input name="starts_at" :label="__('Start time')" type="time" value="10:00" />
            <x-input name="duration_minutes" :label="__('Duration (minutes)')" type="number" min="5" value="60" required />
            <x-input name="max_score" :label="__('Maximum score')" type="number" min="1" value="100" required />
            <x-input name="pass_score" :label="__('Pass mark')" type="number" min="0" value="50" required />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
