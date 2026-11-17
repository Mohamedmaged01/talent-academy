@extends('layouts.portal')

@section('title', $article->exists ? __('Edit Article') : __('New Article'))

@section('content')
    <x-page-header :title="$article->exists ? __('Edit Article') : __('New Article')" :back="route('admin.news.index')" />

    <form method="POST" enctype="multipart/form-data" action="{{ $article->exists ? route('admin.news.update', $article) : route('admin.news.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div class="card xl:col-span-2" x-data="{ tab: 'ar' }">
            <div class="card-header">
                <h2 class="card-title">{{ __('Content') }}</h2>
                <div class="flex gap-1 rounded-xl bg-canvas p-1 text-sm">
                    <button type="button" class="rounded-lg px-3 py-1" :class="tab === 'ar' && 'bg-white shadow-sm font-semibold'" @click="tab = 'ar'">العربية</button>
                    <button type="button" class="rounded-lg px-3 py-1" :class="tab === 'en' && 'bg-white shadow-sm font-semibold'" @click="tab = 'en'">English</button>
                </div>
            </div>
            @foreach (['ar' => 'rtl', 'en' => 'ltr'] as $lang => $direction)
                <div class="grid gap-4 p-6" x-show="tab === '{{ $lang }}'" @if ($lang === 'en') x-cloak @endif>
                    <x-input :name="'title_'.$lang" :label="__('Title')" :value="$article->{'title_'.$lang}" required :dir="$direction" />
                    <x-input :name="'category_'.$lang" :label="__('Category')" :value="$article->{'category_'.$lang}" :dir="$direction" />
                    <x-input :name="'excerpt_'.$lang" :label="__('Summary')" type="textarea" rows="2" :value="$article->{'excerpt_'.$lang}" :dir="$direction" />
                    <x-input :name="'body_'.$lang" :label="__('Article body')" type="textarea" rows="12" :value="$article->{'body_'.$lang}" :dir="$direction" :hint="__('Separate paragraphs with an empty line.')" />
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Publishing') }}</h2></div>
                <div class="grid gap-4 p-6">
                    <x-input name="slug" :label="__('Web address (slug)')" :value="$article->slug" dir="ltr" :hint="__('Leave empty to generate from the English title.')" />
                    <x-input name="author_name" :label="__('Author')" :value="$article->author_name" />
                    <x-input name="published_at" :label="__('Publish date')" type="datetime-local" :value="$article->published_at?->format('Y-m-d\TH:i')" />
                    <div>
                        <label class="label" for="image">{{ __('Cover image') }}</label>
                        @if ($article->exists)<img src="{{ $article->imageUrl() }}" alt="" class="mb-2 aspect-[16/9] w-full rounded-xl object-cover">@endif
                        <input id="image" type="file" name="image" accept="image/*" class="input">
                        @error('image')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published))> {{ __('Published') }}
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                <a href="{{ route('admin.news.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
