@extends('layouts.portal')

@section('title', __('Issue Certificate'))

@section('content')
    <x-page-header :title="__('Issue Certificate')" :subtitle="__('Issue a single certificate manually.')" :back="route('admin.certificates.index')" />

    <form method="POST" action="{{ route('admin.certificates.store') }}" class="card max-w-3xl">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="student_id" :label="__('Student')" :value="$selectedStudent" required class="sm:col-span-2"
                      :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name.' — '.$s->code])" :placeholder="__('Select…')" />
            <x-select name="batch_id" :label="__('Class (optional)')" class="sm:col-span-2"
                      :options="$batches->mapWithKeys(fn ($b) => [$b->id => $b->name.' — '.\Illuminate\Support\Str::limit($b->course->tr('title'), 40)])" :placeholder="__('None')" />
            <x-input name="title_ar" :label="__('Certificate title (Arabic)')" required :value="'شهادة إتمام: '" />
            <x-input name="title_en" :label="__('Certificate title (English)')" required dir="ltr" :value="'Certificate of Completion: '" />
            <x-select name="grade" :label="__('Grade')" :options="['Excellent' => __('Excellent'), 'Very Good' => __('Very Good'), 'Good' => __('Good'), 'Pass' => __('Pass')]" :placeholder="__('None')" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.certificates.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-award class="size-4" />{{ __('Issue') }}</button>
        </div>
    </form>
@endsection
