@extends('layouts.portal')

@section('title', __('Digital Library'))

@section('content')
    <x-page-header :title="__('Digital Library')" :subtitle="__('Books, training kits and references for every program.')" />

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-4">
        <input type="search" name="q" value="{{ request('q') }}" class="input sm:col-span-2" placeholder="{{ __('Search by title or author…') }}">
        <select name="type" class="input" onchange="this.form.submit()" aria-label="{{ __('Type') }}">
            <option value="">{{ __('All types') }}</option>
            @foreach (\App\Models\LibraryItem::TYPES as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('library.'.$type) }}</option>
            @endforeach
        </select>
        <select name="pathway" class="input" onchange="this.form.submit()" aria-label="{{ __('Pathway') }}">
            <option value="">{{ __('All pathways') }}</option>
            <option value="academic" @selected(request('pathway') === 'academic')>{{ __('Academic') }}</option>
            <option value="training" @selected(request('pathway') === 'training')>{{ __('Training') }}</option>
        </select>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($items as $item)
            <div class="card flex flex-col p-5">
                <div class="flex items-start gap-3">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand"><x-dynamic-component :component="'lucide-'.$item->icon()" class="size-6" /></span>
                    <div class="min-w-0">
                        <p class="font-bold leading-6 text-navy">{{ $item->title }}</p>
                        @if ($item->author)<p class="text-xs text-muted">{{ $item->author }}</p>@endif
                    </div>
                </div>
                @if ($item->description)<p class="mt-3 line-clamp-3 flex-1 text-sm text-muted">{{ $item->description }}</p>@else<div class="flex-1"></div>@endif
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="badge bg-brand-50 text-brand">{{ __('library.'.$item->type) }}</span>
                    @if ($item->course)<span class="badge bg-slate-100 text-slate-600">{{ $item->course->tr('title') }}</span>@endif
                    @if ($item->pages)<span class="text-muted">{{ __(':count pages', ['count' => $item->pages]) }}</span>@endif
                </div>
                <a href="{{ route('library.open', $item) }}" class="btn btn-outline mt-4" @if (! $item->file_path) target="_blank" rel="noopener" @endif>
                    @if ($item->file_path)<x-lucide-download class="size-4" />{{ __('Download') }}@else<x-lucide-external-link class="size-4" />{{ __('Open') }}@endif
                </a>
            </div>
        @empty
            <div class="card sm:col-span-2 xl:col-span-3"><x-empty icon="library" :title="__('Nothing in the library yet')" /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
