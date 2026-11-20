@extends('layouts.portal')

@section('title', $item->exists ? __('Edit library item') : __('Add to the library'))

@section('content')
    <x-page-header :title="$item->exists ? __('Edit library item') : __('Add to the library')" :back="route('admin.library.index')" />

    <form method="POST" action="{{ $item->exists ? route('admin.library.update', $item) : route('admin.library.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-input name="title" :label="__('Title')" required :value="$item->title" class="sm:col-span-2" dir="auto" />
            <x-input name="author" :label="__('Author / publisher')" :value="$item->author" dir="auto" />
            <x-input name="pages" :label="__('Pages')" type="number" min="1" :value="$item->pages" />
            <x-select name="type" :label="__('Type')" required :value="$item->type" :options="collect(\App\Models\LibraryItem::TYPES)->mapWithKeys(fn ($t) => [$t => __('library.'.$t)])" />
            <x-select name="pathway" :label="__('Pathway')" required :value="$item->pathway" :options="collect(\App\Models\LibraryItem::PATHWAYS)->mapWithKeys(fn ($p) => [$p => __('pathway.'.$p)])" />
            <x-select name="course_id" :label="__('Linked course')" :value="$item->course_id" class="sm:col-span-2" :options="$courses->mapWithKeys(fn ($c) => [$c->id => $c->tr('title')])" :placeholder="__('None')" />
            <x-input name="description" :label="__('Description')" type="textarea" rows="3" :value="$item->description" class="sm:col-span-2" dir="auto" />
            <div class="sm:col-span-2">
                <label class="label" for="file">{{ __('File') }}</label>
                <input id="file" type="file" name="file" class="input">
                <p class="hint">
                    @if ($item->original_name){{ __('Current file: :name', ['name' => $item->original_name]) }} · @endif
                    {{ __('PDF, EPUB, Office, zip, images or media — up to 50 MB.') }}
                </p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-input name="url" :label="__('…or an external link')" type="url" :value="$item->url" dir="ltr" class="sm:col-span-2" />
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item->is_published))> {{ __('Visible to teachers and students') }}
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.library.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
