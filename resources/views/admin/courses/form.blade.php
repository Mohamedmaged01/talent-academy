@extends('layouts.portal')

@section('title', $course->exists ? __('Edit Program') : __('Add Program'))

@section('content')
    <x-page-header :title="$course->exists ? __('Edit Program') : __('Add Program')" :back="route('admin.courses.index')" />

    <form method="POST" enctype="multipart/form-data" action="{{ $course->exists ? route('admin.courses.update', $course) : route('admin.courses.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($course->exists) @method('PUT') @endif

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Content (both languages)') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="title_ar" :label="__('Title (Arabic)')" :value="$course->title_ar" required />
                <x-input name="title_en" :label="__('Title (English)')" :value="$course->title_en" required dir="ltr" />
                <x-input name="category_ar" :label="__('Category (Arabic)')" :value="$course->category_ar" />
                <x-input name="category_en" :label="__('Category (English)')" :value="$course->category_en" dir="ltr" />
                <x-input name="level_ar" :label="__('Level / grade (Arabic)')" :value="$course->level_ar" />
                <x-input name="level_en" :label="__('Level / grade (English)')" :value="$course->level_en" dir="ltr" />
                <x-input name="description_ar" :label="__('Description (Arabic)')" type="textarea" rows="5" :value="$course->description_ar" />
                <x-input name="description_en" :label="__('Description (English)')" type="textarea" rows="5" :value="$course->description_en" dir="ltr" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Settings') }}</h2></div>
                <div class="grid gap-4 p-6">
                    <x-input name="code" :label="__('Program code')" :value="$course->code" required dir="ltr" :hint="__('Used in the web address, e.g. AI-PRO-01')" />
                    <x-select name="pathway" :label="__('Pathway')" :value="$course->pathway" :options="['academic' => __('Academic'), 'training' => __('Training')]" required />
                    <div class="grid grid-cols-2 gap-3">
                        <x-input name="duration_weeks" :label="__('Weeks')" type="number" min="1" :value="$course->duration_weeks" />
                        <x-input name="duration_hours" :label="__('Hours')" type="number" min="1" :value="$course->duration_hours" />
                    </div>
                    <x-input name="price" :label="__('Fees (EGP)')" type="number" step="0.01" min="0" :value="$course->price" required />
                    <x-input name="sort_order" :label="__('Display order')" type="number" min="0" :value="$course->sort_order" />
                    <div>
                        <label class="label" for="image">{{ __('Cover image') }}</label>
                        @if ($course->exists)<img src="{{ $course->imageUrl() }}" alt="" class="mb-2 aspect-[16/9] w-full rounded-xl object-cover">@endif
                        <input id="image" type="file" name="image" accept="image/*" class="input">
                        @error('image')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published))> {{ __('Show on public website') }}
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
