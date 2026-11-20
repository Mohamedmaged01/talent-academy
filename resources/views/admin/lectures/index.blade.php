@extends('layouts.portal')

@section('title', __('Recorded Lectures'))

@section('content')
    <x-page-header :title="__('Recorded Lectures')" :subtitle="__('Recordings teachers have shared with their classes.')" :back="route('admin.exams.index')" />

    <div class="card">
        <form method="GET" class="border-b border-line p-4">
            <select name="batch" class="input sm:max-w-xs" onchange="this.form.submit()" aria-label="{{ __('Class') }}">
                <option value="">{{ __('All classes') }}</option>
                @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" @selected((string) request('batch') === (string) $batch->id)>{{ $batch->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Lecture') }}</th><th>{{ __('Class') }}</th><th>{{ __('Teacher') }}</th><th>{{ __('Viewers') }}</th><th>{{ __('Added') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($lectures as $lecture)
                        <tr>
                            <td><p class="font-semibold text-navy">{{ $lecture->title }}</p>@unless ($lecture->is_published)<x-status value="draft" />@endunless</td>
                            <td><p>{{ $lecture->batch->name }}</p><p class="text-xs text-muted">{{ $lecture->batch->course->tr('title') }}</p></td>
                            <td class="text-muted">{{ $lecture->teacher?->name }}</td>
                            <td class="tabular-nums">{{ $lecture->views_count }}</td>
                            <td class="text-xs text-muted whitespace-nowrap">{{ $lecture->created_at->translatedFormat('j M Y') }}</td>
                            <td class="text-end whitespace-nowrap">
                                <a href="{{ $lecture->video_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="{{ __('Open') }}"><x-lucide-external-link class="size-4" /></a>
                                @if (auth()->user()->canAdmin('exams', 'manage'))
                                    <x-confirm-delete :action="route('admin.lectures.destroy', $lecture)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="clapperboard" :title="__('No recorded lectures yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $lectures->links() }}
    </div>
@endsection
