@extends('layouts.portal')

@section('title', __('Site Content & Media'))

@section('content')
    <x-page-header :title="__('Site Content & Media')" :subtitle="__('Edit the public website\'s main texts and images. Empty fields use the built-in wording.')">
        <a href="{{ route('admin.media.index') }}" class="btn btn-outline"><x-lucide-images class="size-4" />{{ __('Media library') }}</a>
        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline"><x-lucide-globe class="size-4" />{{ __('View website') }}</a>
    </x-page-header>

    <form method="POST" action="{{ route('admin.content.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Homepage hero') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="hero_title_ar" :label="__('Title (Arabic)')" :value="$values['hero_title_ar']" :placeholder="__('Talent Academy')" />
                <x-input name="hero_title_en" :label="__('Title (English)')" :value="$values['hero_title_en']" dir="ltr" placeholder="Talent Academy" />
                <x-input name="hero_tagline_ar" :label="__('Tagline (Arabic)')" :value="$values['hero_tagline_ar']" />
                <x-input name="hero_tagline_en" :label="__('Tagline (English)')" :value="$values['hero_tagline_en']" dir="ltr" />
                <x-input name="hero_text_ar" :label="__('Intro text (Arabic)')" type="textarea" rows="2" :value="$values['hero_text_ar']" />
                <x-input name="hero_text_en" :label="__('Intro text (English)')" type="textarea" rows="2" :value="$values['hero_text_en']" dir="ltr" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Notice bar') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="notice_bar_ar" :label="__('Message (Arabic)')" :value="$values['notice_bar_ar']" :hint="__('Shown in a yellow bar at the top of every public page. Leave empty to hide it.')" />
                <x-input name="notice_bar_en" :label="__('Message (English)')" :value="$values['notice_bar_en']" dir="ltr" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('About page') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="about_intro_ar" :label="__('Opening paragraph (Arabic)')" type="textarea" rows="4" :value="$values['about_intro_ar']" />
                <x-input name="about_intro_en" :label="__('Opening paragraph (English)')" type="textarea" rows="4" :value="$values['about_intro_en']" dir="ltr" />
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ __('Website images') }}</h2>
                <a href="{{ route('admin.media.index') }}" class="text-xs font-semibold text-brand">{{ __('Upload images') }}</a>
            </div>
            @php
                $slots = [
                    'hero_image' => [__('Homepage hero portrait'), 'images/director-portrait.png'],
                    'pathway_academic_image' => [__('Academic pathway card'), 'images/pathway_academic.jpg'],
                    'pathway_training_image' => [__('Training pathway card'), 'images/pathway_training.jpg'],
                    'about_image' => [__('About page photo'), 'images/students-lab.jpg'],
                    'login_image' => [__('Login page background'), 'images/login-hero.jpg'],
                ];
            @endphp
            <div class="grid gap-6 p-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($slots as $key => [$label, $fallback])
                    <div x-data="{ value: @js((string) old($key, $values[$key])) }">
                        <p class="label">{{ $label }}</p>
                        <input type="hidden" name="{{ $key }}" :value="value">
                        <div class="grid grid-cols-4 gap-2 rounded-xl border border-line p-2">
                            <button type="button" @click="value = ''" :class="value === '' ? 'ring-2 ring-brand' : ''" class="relative overflow-hidden rounded-lg" title="{{ __('Default image') }}">
                                <img src="{{ asset($fallback) }}" alt="" class="aspect-square w-full object-cover">
                                <span class="absolute inset-x-0 bottom-0 bg-navy/70 py-0.5 text-[9px] text-white">{{ __('Default') }}</span>
                            </button>
                            @foreach ($media as $item)
                                <button type="button" @click="value = @js($item->path)" :class="value === @js($item->path) ? 'ring-2 ring-brand' : ''" class="overflow-hidden rounded-lg" title="{{ $item->title }}">
                                    <img src="{{ $item->url() }}" alt="{{ $item->alt }}" class="aspect-square w-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                        @error($key)<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end"><button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save content') }}</button></div>
    </form>
@endsection
