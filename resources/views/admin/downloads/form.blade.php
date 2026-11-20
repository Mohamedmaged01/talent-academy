@extends('layouts.portal')

@section('title', $download->exists ? __('Edit download') : __('Add file'))

@section('content')
    <x-page-header :title="$download->exists ? __('Edit download') : __('Add file')" :back="route('admin.downloads.index')" />

    <form method="POST" action="{{ $download->exists ? route('admin.downloads.update', $download) : route('admin.downloads.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        @if ($download->exists) @method('PUT') @endif
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-input name="title_ar" :label="__('Title (Arabic)')" required :value="$download->title_ar" />
            <x-input name="title_en" :label="__('Title (English)')" :value="$download->title_en" dir="ltr" />
            <x-input name="description_ar" :label="__('Description (Arabic)')" type="textarea" rows="2" :value="$download->description_ar" />
            <x-input name="description_en" :label="__('Description (English)')" type="textarea" rows="2" :value="$download->description_en" dir="ltr" />
            <x-select name="category" :label="__('Category')" required :value="$download->category" :options="collect(\App\Models\Download::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __('downloads.'.$c)])" />
            <x-select name="audience" :label="__('Who can download it?')" required :value="$download->audience" :options="collect(\App\Models\Download::AUDIENCES)->mapWithKeys(fn ($a) => [$a => __('download_audience.'.$a)])" />
            <div class="sm:col-span-2">
                <label class="label" for="file">{{ __('File') }}</label>
                <input id="file" type="file" name="file" class="input">
                <p class="hint">@if ($download->original_name){{ __('Current file: :name', ['name' => $download->original_name]) }} · @endif{{ __('Up to 20 MB.') }}</p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-input name="url" :label="__('…or an external link')" type="url" :value="$download->url" dir="ltr" class="sm:col-span-2" />
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $download->is_published))> {{ __('Published') }}
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.downloads.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
