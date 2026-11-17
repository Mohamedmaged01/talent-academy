@extends('layouts.site')

@section('title', __('Apply Now'))

@section('content')
    @include('site._banner', ['title' => __('Online Application'), 'subtitle' => __('Fill in your details and our admissions team will contact you within one working day.')])

    <section class="mx-auto max-w-4xl px-6 py-12" x-data="{ pathway: @js(old('pathway', $selectedPathway)) }">
        <x-flash />

        <form method="POST" action="{{ route('admissions.store') }}" class="card divide-y divide-line">
            @csrf
            <div class="p-6">
                <h2 class="card-title">{{ __('1. Choose your path') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (['academic' => ['graduation-cap', __('Academic Education'), __('American Diploma & foundation classes')], 'training' => ['monitor', __('Training Programs'), __('Professional & technical courses')]] as $value => [$icon, $title, $desc])
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition"
                               :class="pathway === '{{ $value }}' ? 'border-brand bg-brand-50' : 'border-line hover:border-brand/50'">
                            <input type="radio" name="pathway" value="{{ $value }}" x-model="pathway" class="mt-1">
                            <span>
                                <span class="flex items-center gap-2 font-bold text-navy"><x-dynamic-component :component="'lucide-'.$icon" class="size-5 text-brand" />{{ $title }}</span>
                                <span class="mt-1 block text-sm text-muted">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('pathway')<p class="field-error">{{ $message }}</p>@enderror

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="course_id" class="label">{{ __('Program') }}</label>
                        <select id="course_id" name="course_id" class="input">
                            <option value="">{{ __('Not sure yet — advise me') }}</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" data-pathway="{{ $course->pathway }}"
                                        x-show="pathway === '{{ $course->pathway }}'"
                                        @selected((string) old('course_id', $selectedCourse) === (string) $course->id)>{{ $course->tr('title') }}</option>
                            @endforeach
                        </select>
                        @error('course_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <x-select name="branch_id" :label="__('Preferred branch')" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('Any branch')" />
                </div>
            </div>

            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <h2 class="card-title sm:col-span-2">{{ __('2. Applicant details') }}</h2>
                <x-input name="full_name" :label="__('Full Name')" required class="sm:col-span-2" />
                <x-input name="phone" :label="__('Mobile number')" type="tel" required dir="ltr" placeholder="01xxxxxxxxx" />
                <x-input name="email" :label="__('Email')" type="email" dir="ltr" />
                <x-input name="grade_level" :label="__('Current grade / level')" :hint="__('e.g. Grade 10, university student, graduate')" />
            </div>

            <div class="grid gap-4 p-6 sm:grid-cols-2" x-show="pathway === 'academic'" x-collapse>
                <h2 class="card-title sm:col-span-2">{{ __('3. Parent / guardian') }}</h2>
                <x-input name="guardian_name" :label="__('Guardian name')" />
                <x-input name="guardian_phone" :label="__('Guardian mobile')" type="tel" dir="ltr" />
            </div>

            <div class="p-6">
                <x-input name="notes" :label="__('Anything we should know?')" type="textarea" rows="3" />
                <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-muted">{{ __('By applying you agree to be contacted by Talent Academy about your application.') }}</p>
                    <button class="btn btn-primary px-8 py-3"><x-lucide-send class="size-4 rtl:-scale-x-100" /> {{ __('Submit Application') }}</button>
                </div>
            </div>
        </form>
    </section>
@endsection
