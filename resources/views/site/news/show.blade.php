@extends('layouts.site')

@section('title', $article->tr('title'))
@section('description', $article->tr('excerpt'))

@section('content')
    <article class="mx-auto max-w-4xl px-6 py-12">
        <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-brand">
            <x-lucide-arrow-right class="size-4 ltr:rotate-180" /> {{ __('All News') }}
        </a>
        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-muted">
            @if ($article->tr('category'))
                <span class="badge bg-brand-50 text-brand">{{ $article->tr('category') }}</span>
            @endif
            <span>{{ $article->published_at?->translatedFormat('j F Y') }}</span>
            @if ($article->author_name)
                <span>· {{ $article->author_name }}</span>
            @endif
        </div>
        <h1 class="mt-3 text-3xl font-extrabold leading-snug text-navy md:text-4xl">{{ $article->tr('title') }}</h1>
        <p class="mt-4 text-lg leading-8 text-muted">{{ $article->tr('excerpt') }}</p>
        <img src="{{ $article->imageUrl() }}" alt="" class="mt-8 aspect-[16/8] w-full rounded-2xl object-cover">
        <div class="prose-body mt-8 text-[17px] text-ink">
            @foreach (preg_split("/\n\s*\n/", (string) $article->tr('body')) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
        <div class="mt-10 rounded-2xl bg-brand-50 p-6 sm:flex sm:items-center sm:justify-between">
            <p class="font-bold text-navy">{{ __('Interested in our programs?') }}</p>
            <a href="{{ route('admissions.create') }}" class="btn btn-primary mt-3 sm:mt-0">{{ __('Apply Now') }}</a>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="border-t border-line bg-canvas py-12">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-xl font-extrabold text-navy">{{ __('More News') }}</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $item)
                        @include('site.news._card', ['article' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
