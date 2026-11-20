@extends('layouts.portal')

@section('title', __('Digital Library'))

@section('content')
    <x-page-header :title="__('Digital Library')" :subtitle="__('Books, training kits and references available to teachers and students.')">
        <a href="{{ route('admin.downloads.index') }}" class="btn btn-outline"><x-lucide-download class="size-4" />{{ __('Downloads Center') }}</a>
        @if (auth()->user()->canAdmin('library', 'manage'))
            <a href="{{ route('admin.library.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add item') }}</a>
        @endif
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Items')" :value="$totals['items']" icon="library" />
        <x-stat :label="__('Training kits')" :value="$totals['kits']" icon="briefcase" tone="violet" />
        <x-stat :label="__('Downloads')" :value="number_format($totals['downloads'])" icon="download" tone="emerald" />
    </div>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-4">
            <input type="search" name="q" value="{{ request('q') }}" class="input sm:col-span-2" placeholder="{{ __('Search by title or author…') }}">
            <select name="type" class="input" onchange="this.form.submit()" aria-label="{{ __('Type') }}">
                <option value="">{{ __('All types') }}</option>
                @foreach (\App\Models\LibraryItem::TYPES as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('library.'.$type) }}</option>
                @endforeach
            </select>
            <select name="pathway" class="input" onchange="this.form.submit()" aria-label="{{ __('Pathway') }}">
                <option value="">{{ __('All pathways') }}</option>
                @foreach (\App\Models\LibraryItem::PATHWAYS as $pathway)
                    <option value="{{ $pathway }}" @selected(request('pathway') === $pathway)>{{ __('pathway.'.$pathway) }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Type') }}</th><th>{{ __('Pathway') }}</th><th>{{ __('Downloads') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <p class="font-semibold text-navy">{{ $item->title }}</p>
                                <p class="text-xs text-muted">{{ $item->author }} @if ($item->course)· {{ $item->course->tr('title') }}@endif</p>
                            </td>
                            <td><span class="badge bg-brand-50 text-brand">{{ __('library.'.$item->type) }}</span></td>
                            <td class="text-muted">{{ __('pathway.'.$item->pathway) }}</td>
                            <td class="tabular-nums">{{ $item->downloads_count }}</td>
                            <td><x-status :value="$item->is_published ? 'published' : 'draft'" /></td>
                            <td class="text-end whitespace-nowrap">
                                <a href="{{ route('library.open', $item) }}" class="btn btn-ghost btn-sm" title="{{ __('Open') }}"><x-lucide-external-link class="size-4" /></a>
                                @if (auth()->user()->canAdmin('library', 'manage'))
                                    <a href="{{ route('admin.library.edit', $item) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.library.destroy', $item)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="library" :title="__('Nothing in the library yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
@endsection
