@extends('layouts.portal')

@section('title', $student->exists ? __('Edit Student') : __('Add Student'))

@section('content')
    <x-page-header :title="$student->exists ? __('Edit Student') : __('Add Student')" :subtitle="$student->code" :back="$student->exists ? route('admin.students.show', $student) : route('admin.students.index')" />

    <form method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($student->exists) @method('PUT') @endif

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Personal details') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="full_name" :label="__('Full name (Arabic)')" :value="$student->full_name" required />
                <x-input name="full_name_en" :label="__('Full name (English)')" :value="$student->full_name_en" dir="ltr" />
                <x-input name="national_id" :label="__('National ID')" :value="$student->national_id" dir="ltr" maxlength="14" />
                <x-select name="gender" :label="__('Gender')" :value="$student->gender" :options="['male' => __('Male'), 'female' => __('Female')]" required />
                <x-input name="birth_date" :label="__('Date of birth')" type="date" :value="$student->birth_date?->toDateString()" />
                <x-input name="phone" :label="__('Phone')" :value="$student->phone" dir="ltr" />
                <x-input name="email" :label="__('Email')" type="email" :value="$student->email" dir="ltr" />
                <x-input name="school_name" :label="__('School / university')" :value="$student->school_name" />
                <x-input name="address" :label="__('Address')" :value="$student->address" class="sm:col-span-2" />
                <x-input name="notes" :label="__('Notes')" type="textarea" rows="3" :value="$student->notes" class="sm:col-span-2" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Enrollment') }}</h2></div>
                <div class="grid gap-4 p-6">
                    <x-select name="branch_id" :label="__('Branch')" :value="$student->branch_id" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" required />
                    <x-select name="pathway" :label="__('Pathway')" :value="$student->pathway" :options="['academic' => __('Academic'), 'training' => __('Training')]" required />
                    <x-input name="grade_level" :label="__('Grade / Level')" :value="$student->grade_level" />
                    <x-select name="status" :label="__('Status')" :value="$student->status" :options="collect(\App\Models\Student::STATUSES)->mapWithKeys(fn ($s) => [$s => __(ucfirst($s))])" required />
                    <x-input name="enrolled_at" :label="__('Enrollment date')" type="date" :value="$student->enrolled_at?->toDateString()" />
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Portal login') }}</h2></div>
                <div class="grid gap-4 p-6">
                    <x-input name="login_email" :label="__('Login email')" type="email" :value="$student->user?->email" dir="ltr" :hint="__('Leave empty if the student does not need a portal account yet.')" />
                    <x-input name="login_password" :label="$student->user ? __('New password') : __('Password')" type="password" autocomplete="new-password" :hint="$student->user ? __('Leave empty to keep the current password.') : __('At least 8 characters.')" />
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary flex-1"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
