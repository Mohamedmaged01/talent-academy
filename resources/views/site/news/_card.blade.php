<article class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
    <a href="{{ route('news.show', $article) }}" class="block aspect-[16/10] overflow-hidden">
        <img src="{{ $article->imageUrl() }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center justify-between gap-2 text-xs">
            @if ($article->tr('category'))
                <span class="badge bg-brand-50 text-brand">{{ $article->tr('category') }}</span>
            @endif
            <time class="text-muted" datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('j F Y') }}</time>
        </div>
        <h3 class="mt-3 font-bold leading-7 text-navy">
            <a href="{{ route('news.show', $article) }}" class="hover:text-brand">{{ $article->tr('title') }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 text-sm leading-6 text-muted">{{ $article->tr('excerpt') }}</p>
        <a href="{{ route('news.show', $article) }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-brand">
            {{ __('Read More') }} <x-lucide-arrow-left class="size-4 ltr:rotate-180" />
        </a>
    </div>
</article>
