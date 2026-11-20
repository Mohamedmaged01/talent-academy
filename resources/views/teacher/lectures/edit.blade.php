@extends('layouts.portal')

@section('title', __('Edit lecture'))

@section('content')
    <x-page-header :title="__('Edit lecture')" :back="route('teacher.lectures.index', ['batch' => $lecture->batch_id])" />

    <form method="POST" action="{{ route('teacher.lectures.update', $lecture) }}" class="card max-w-3xl">
        @csrf @method('PUT')
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="batch_id" :label="__('Class')" required :value="$lecture->batch_id" class="sm:col-span-2" :options="$batches->mapWithKeys(fn ($b) => [$b->id => $b->name])" />
            <x-input name="title" :label="__('Title')" required :value="$lecture->title" class="sm:col-span-2" />
            <x-input name="video_url" :label="__('Video link')" type="url" required dir="ltr" :value="$lecture->video_url" />
            <x-input name="duration_minutes" :label="__('Duration (minutes)')" type="number" min="1" :value="$lecture->duration_minutes" />
            <x-input name="description" :label="__('Description')" type="textarea" rows="4" :value="$lecture->description" class="sm:col-span-2" />
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked($lecture->is_published)> {{ __('Visible to students now') }}
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('teacher.lectures.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
