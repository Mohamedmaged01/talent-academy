@extends('layouts.portal')

@section('title', __('Media library'))

@section('content')
    <x-page-header :title="__('Media library')" :subtitle="__('Images and documents for the website. Copy a file\'s link to use it in news articles.')" :back="route('admin.content.edit')" />

    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card mb-6 grid gap-3 p-4 sm:grid-cols-4 sm:items-end">
        @csrf
        <div class="sm:col-span-2">
            <label class="label" for="files">{{ __('Files') }}</label>
            <input id="files" type="file" name="files[]" multiple required accept="image/*,.pdf" class="input">
            <p class="hint">{{ __('Images or PDF, up to 8 MB each, 10 at a time.') }}</p>
            @error('files')<p class="field-error">{{ $message }}</p>@enderror
            @error('files.*')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <x-input name="title" :label="__('Title (optional)')" />
        <button class="btn btn-primary"><x-lucide-upload class="size-4" />{{ __('Upload') }}</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($items as $item)
            <div class="card overflow-hidden" x-data="{ edit: false, copied: false }">
                @if ($item->isImage())
                    <img src="{{ $item->url() }}" alt="{{ $item->alt }}" class="aspect-video w-full bg-canvas object-cover" loading="lazy">
                @else
                    <div class="grid aspect-video place-items-center bg-canvas text-muted"><x-lucide-file-text class="size-10" /></div>
                @endif
                <div class="p-4">
                    <p class="truncate font-semibold text-navy">{{ $item->title }}</p>
                    <p class="text-xs text-muted">{{ $item->humanSize() }} · {{ $item->created_at->translatedFormat('j M Y') }}</p>
                    <div class="mt-3 flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline btn-sm" @click="navigator.clipboard.writeText(@js($item->url())); copied = true; setTimeout(() => copied = false, 1500)">
                            <x-lucide-link class="size-4" /><span x-text="copied ? @js(__('Copied')) : @js(__('Copy link'))"></span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="edit = ! edit" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></button>
                        <x-confirm-delete :action="route('admin.media.destroy', $item)" />
                    </div>
                    <form method="POST" action="{{ route('admin.media.update', $item) }}" x-show="edit" x-cloak class="mt-3 grid gap-2">
                        @csrf @method('PATCH')
                        <input name="title" value="{{ $item->title }}" required class="input py-1.5 text-sm" aria-label="{{ __('Title') }}">
                        <input name="alt" value="{{ $item->alt }}" class="input py-1.5 text-sm" placeholder="{{ __('Alt text (describes the image)') }}" aria-label="{{ __('Alt text') }}">
                        <button class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-empty icon="images" :title="__('No media yet')" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
