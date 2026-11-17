@extends('layouts.portal')

@section('title', __('Assignments'))

@section('content')
    <x-page-header :title="__('Assignments')" :subtitle="__('Homework and projects for your classes.')">
        <a href="{{ route('teacher.assignments.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('New Assignment') }}</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Assignment') }}</th><th>{{ __('Class') }}</th><th>{{ __('Due') }}</th><th>{{ __('Submitted') }}</th><th>{{ __('Graded') }}</th></tr></thead>
                <tbody>
                    @forelse ($assignments as $assignment)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('teacher.assignments.show', $assignment) }}'">
                            <td class="font-semibold text-navy">{{ $assignment->title }}</td>
                            <td class="text-muted">{{ $assignment->batch->name }}</td>
                            <td @class(['text-xs', 'text-muted' => $assignment->isOverdue(), 'font-semibold text-brand' => ! $assignment->isOverdue()])>{{ $assignment->due_at->translatedFormat('j M Y H:i') }}</td>
                            <td class="tabular-nums">{{ $assignment->submissions_count }}</td>
                            <td>
                                @if ($assignment->submissions_count && $assignment->graded_count < $assignment->submissions_count)
                                    <span class="badge bg-violet-50 text-violet-700">{{ $assignment->submissions_count - $assignment->graded_count }} {{ __('to grade') }}</span>
                                @else
                                    <span class="tabular-nums text-muted">{{ $assignment->graded_count }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="notebook-pen" :title="__('No assignments yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assignments->links() }}
    </div>
@endsection
