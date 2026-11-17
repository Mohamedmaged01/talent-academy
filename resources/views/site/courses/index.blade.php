@extends('layouts.site')

@section('title', __('Programs & Courses'))

@section('content')
    @include('site._banner', [
        'title' => match ($pathway) { 'academic' => __('Academic Programs'), 'training' => __('Training Programs'), default => __('Programs & Courses') },
        'subtitle' => __('American Diploma, foundation classes and professional training — all under one academy.'),
    ])

    <section class="mx-auto max-w-7xl px-6 py-12">
        <form method="GET" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <div class="flex gap-2">
                @foreach (['' => __('All'), 'academic' => __('Academic'), 'training' => __('Training')] as $value => $label)
                    <a href="{{ route('courses.index', array_filter(['pathway' => $value, 'q' => request('q')])) }}"
                       @class(['btn btn-sm', 'btn-primary' => $pathway === ($value ?: null), 'btn-outline' => $pathway !== ($value ?: null)])>{{ $label }}</a>
                @endforeach
            </div>
            <input type="hidden" name="pathway" value="{{ $pathway }}">
            <div class="relative flex-1">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search programs…') }}" class="input ps-9">
            </div>
            <button class="btn btn-primary">{{ __('Search') }}</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($courses as $course)
                <article class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
                    <a href="{{ route('courses.show', $course) }}" class="relative block aspect-[16/9] overflow-hidden">
                        <img src="{{ $course->imageUrl() }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
                        <x-status :value="$course->pathway" class="absolute top-3 start-3 bg-white/95" />
                    </a>
                    <div class="flex flex-1 flex-col p-5">
                        @if ($course->tr('category') || $course->tr('level'))
                            <p class="text-xs font-semibold text-brand">{{ $course->tr('category') ?: $course->tr('level') }}</p>
                        @endif
                        <h3 class="mt-1.5 font-bold leading-7 text-navy"><a href="{{ route('courses.show', $course) }}" class="hover:text-brand">{{ $course->tr('title') }}</a></h3>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-muted">{{ $course->tr('description') }}</p>
                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-line pt-4 text-sm">
                            <span class="flex items-center gap-3 text-muted">
                                @if ($course->duration_weeks)<span class="inline-flex items-center gap-1"><x-lucide-calendar class="size-4" />{{ trans_choice(':count week|:count weeks', $course->duration_weeks) }}</span>@endif
                                @if ($course->duration_hours)<span class="inline-flex items-center gap-1"><x-lucide-clock class="size-4" />{{ $course->duration_hours }} {{ __('hrs') }}</span>@endif
                            </span>
                            @if ((float) $course->price > 0)
                                <span class="font-bold text-navy">{{ money($course->price, false) }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-empty icon="book-open" :title="__('No programs match your search.')" class="card sm:col-span-2 lg:col-span-3" />
            @endforelse
        </div>
    </section>
@endsection
