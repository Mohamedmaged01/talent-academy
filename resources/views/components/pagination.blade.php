@if ($paginator->hasPages())
    <nav role="navigation" class="flex items-center justify-between gap-3 border-t border-line px-5 py-3 text-sm">
        <p class="text-muted">
            {{ __('Showing :from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-outline opacity-40"><x-lucide-chevron-left class="size-4 rtl:rotate-180" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-outline" aria-label="{{ __('Previous') }}"><x-lucide-chevron-left class="size-4 rtl:rotate-180" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-muted">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm btn-primary min-w-8">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm btn-outline hidden min-w-8 sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-outline" aria-label="{{ __('Next') }}"><x-lucide-chevron-right class="size-4 rtl:rotate-180" /></a>
            @else
                <span class="btn btn-sm btn-outline opacity-40"><x-lucide-chevron-right class="size-4 rtl:rotate-180" /></span>
            @endif
        </div>
    </nav>
@endif
