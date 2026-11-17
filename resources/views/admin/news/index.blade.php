@extends('layouts.portal')

@section('title', __('News & Blog'))

@section('content')
    <x-page-header :title="__('News & Blog')" :subtitle="__('Articles shown on the public website.')">
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('New Article') }}</a>
    </x-page-header>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($articles as $article)
            <div class="card flex flex-col overflow-hidden">
                <img src="{{ $article->imageUrl() }}" alt="" class="aspect-[16/9] w-full object-cover">
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-muted">{{ $article->published_at?->translatedFormat('j M Y') }}</span>
                        @if ($article->is_published)<x-status value="published" />@else<x-status value="draft" />@endif
                    </div>
                    <p class="mt-2 font-bold leading-7 text-navy">{{ $article->tr('title') }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $article->tr('excerpt') }}</p>
                    <div class="mt-auto flex gap-2 pt-4">
                        <a href="{{ route('news.show', $article) }}" target="_blank" class="btn btn-ghost btn-sm"><x-lucide-external-link class="size-4" /></a>
                        <a href="{{ route('admin.news.edit', $article) }}" class="btn btn-outline btn-sm flex-1"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
                        <x-confirm-delete :action="route('admin.news.destroy', $article)" />
                    </div>
                </div>
            </div>
        @empty
            <x-empty icon="newspaper" :title="__('No articles yet')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
    <div class="card mt-5">{{ $articles->links() }}</div>
@endsection
