@extends('layouts.portal')

@section('title', $teacher->exists ? __('Edit Instructor') : __('Add Instructor'))

@section('content')
    <x-page-header :title="$teacher->exists ? __('Edit Instructor') : __('Add Instructor')" :back="$teacher->exists ? route('admin.teachers.show', $teacher) : route('admin.teachers.index')" />

    <form method="POST" action="{{ $teacher->exists ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($teacher->exists) @method('PUT') @endif

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Professional profile') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="name" :label="__('Full name')" :value="$teacher->name" required class="sm:col-span-2" />
                <x-select name="instructor_type" :label="__('Instructor type')" :value="$profile->instructor_type" required
                          :options="collect(\App\Models\TeacherProfile::TYPES)->mapWithKeys(fn ($t) => [$t => __(\Illuminate\Support\Str::headline($t))])" />
                <x-input name="national_id" :label="__('National ID')" :value="$profile->national_id" dir="ltr" maxlength="14" />
                <x-input name="title_ar" :label="__('Job title (Arabic)')" :value="$profile->title_ar" />
                <x-input name="title_en" :label="__('Job title (English)')" :value="$profile->title_en" dir="ltr" />
                <x-input name="specialization_ar" :label="__('Specialization (Arabic)')" :value="$profile->specialization_ar" />
                <x-input name="specialization_en" :label="__('Specialization (English)')" :value="$profile->specialization_en" dir="ltr" />
                <x-input name="office_hours" :label="__('Office hours')" :value="$profile->office_hours" class="sm:col-span-2" />
                <x-input name="bio" :label="__('Short bio')" type="textarea" rows="3" :value="$profile->bio" class="sm:col-span-2" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Account & employment') }}</h2></div>
                <div class="grid gap-4 p-6">
                    <x-input name="email" :label="__('Login email')" type="email" :value="$teacher->email" required dir="ltr" />
                    <x-input name="password" :label="$teacher->exists ? __('New password') : __('Password')" type="password" :required="! $teacher->exists" autocomplete="new-password" :hint="$teacher->exists ? __('Leave empty to keep the current password.') : __('At least 8 characters.')" />
                    <x-input name="phone" :label="__('Phone')" :value="$teacher->phone" dir="ltr" />
                    <x-select name="branch_id" :label="__('Home branch')" :value="$teacher->branch_id" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('All branches')" />
                    <x-input name="hire_date" :label="__('Hire date')" type="date" :value="$profile->hire_date?->toDateString()" />
                    <x-input name="monthly_salary" :label="__('Monthly salary (EGP)')" type="number" step="0.01" min="0" :value="$profile->monthly_salary" />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $teacher->is_active ?? true))> {{ __('Account active') }}
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
