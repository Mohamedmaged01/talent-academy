@extends('layouts.portal')

@section('title', __('Programs & Courses'))

@section('content')
    <x-page-header :title="__('Programs & Courses')" :subtitle="__('The catalog shown on the public website.')">
        <a href="{{ route('admin.courses.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add Program') }}</a>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <div class="relative flex-1">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Title or code…') }}" class="input ps-9">
            </div>
            <select name="pathway" class="input sm:w-48" onchange="this.form.submit()">
                <option value="">{{ __('All pathways') }}</option>
                <option value="academic" @selected(request('pathway') === 'academic')>{{ __('Academic') }}</option>
                <option value="training" @selected(request('pathway') === 'training')>{{ __('Training') }}</option>
            </select>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Program') }}</th><th>{{ __('Pathway') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Fees') }}</th><th>{{ __('Classes') }}</th><th>{{ __('Website') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $course->imageUrl() }}" alt="" class="size-11 rounded-lg object-cover">
                                    <div class="min-w-0">
                                        <p class="max-w-xs truncate font-semibold text-navy">{{ $course->tr('title') }}</p>
                                        <p class="font-latin text-xs text-muted" dir="ltr">{{ $course->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><x-status :value="$course->pathway" /></td>
                            <td class="text-muted">{{ $course->duration_weeks ? trans_choice(':count week|:count weeks', $course->duration_weeks) : ($course->tr('level') ?? '—') }}</td>
                            <td class="tabular-nums">{{ (float) $course->price ? money($course->price, false) : '—' }}</td>
                            <td class="tabular-nums">{{ $course->batches_count }}</td>
                            <td>@if ($course->is_published)<x-status value="published" />@else<x-status value="draft" />@endif</td>
                            <td class="whitespace-nowrap text-end">
                                <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('View on website') }}"><x-lucide-external-link class="size-4" /></a>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                                <x-confirm-delete :action="route('admin.courses.destroy', $course)" :message="__('Programs with classes are unpublished instead of deleted. Continue?')" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="book-open" :title="__('No programs yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $courses->links() }}
    </div>
@endsection
