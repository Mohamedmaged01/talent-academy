@extends('layouts.portal')

@section('title', __('Downloads Center'))

@section('content')
    <x-page-header :title="__('Downloads Center')" :subtitle="__('Files for the public website and the portals, each shown only to its audience.')">
        <a href="{{ route('downloads.public') }}" target="_blank" class="btn btn-outline"><x-lucide-globe class="size-4" />{{ __('Public page') }}</a>
        @if (auth()->user()->canAdmin('library', 'manage'))
            <a href="{{ route('admin.downloads.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add file') }}</a>
        @endif
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-3">
            <select name="category" class="input" onchange="this.form.submit()" aria-label="{{ __('Category') }}">
                <option value="">{{ __('All categories') }}</option>
                @foreach (\App\Models\Download::CATEGORIES as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ __('downloads.'.$category) }}</option>
                @endforeach
            </select>
            <select name="audience" class="input" onchange="this.form.submit()" aria-label="{{ __('Audience') }}">
                <option value="">{{ __('All audiences') }}</option>
                @foreach (\App\Models\Download::AUDIENCES as $audience)
                    <option value="{{ $audience }}" @selected(request('audience') === $audience)>{{ __('download_audience.'.$audience) }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Category') }}</th><th>{{ __('Audience') }}</th><th>{{ __('File') }}</th><th>{{ __('Downloads') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($downloads as $download)
                        <tr>
                            <td><p class="font-semibold text-navy">{{ $download->tr('title') }}</p>@unless ($download->is_published)<x-status value="draft" />@endunless</td>
                            <td class="text-muted">{{ __('downloads.'.$download->category) }}</td>
                            <td><span class="badge bg-sky-50 text-sky-700">{{ __('download_audience.'.$download->audience) }}</span></td>
                            <td class="text-xs text-muted" dir="ltr">{{ $download->extension() }} {{ $download->humanSize() }}</td>
                            <td class="tabular-nums">{{ $download->downloads_count }}</td>
                            <td class="text-end whitespace-nowrap">
                                <a href="{{ route('downloads.open', $download) }}" class="btn btn-ghost btn-sm" title="{{ __('Open') }}"><x-lucide-external-link class="size-4" /></a>
                                @if (auth()->user()->canAdmin('library', 'manage'))
                                    <a href="{{ route('admin.downloads.edit', $download) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.downloads.destroy', $download)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="download" :title="__('No files to download yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $downloads->links() }}
    </div>
@endsection
