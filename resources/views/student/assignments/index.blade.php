@extends('layouts.portal')

@section('title', __('Assignments'))

@section('content')
    <x-page-header :title="__('Assignments')" />

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Assignment') }}</th><th>{{ __('Course') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th><th>{{ __('Score') }}</th></tr></thead>
                <tbody>
                    @forelse ($assignments as $assignment)
                        @php $submission = $submissions[$assignment->id] ?? null; @endphp
                        <tr class="cursor-pointer" onclick="window.location='{{ route('student.assignments.show', $assignment) }}'">
                            <td class="font-semibold text-navy">{{ $assignment->title }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($assignment->batch->course->tr('title'), 40) }}</td>
                            <td class="text-xs text-muted">{{ $assignment->due_at->translatedFormat('j M Y H:i') }}</td>
                            <td>
                                @if ($submission?->graded_at)<x-status value="graded" />
                                @elseif ($submission)<x-status value="submitted" />
                                @else<x-status :value="$assignment->isOverdue() ? 'missing' : 'pending'" />@endif
                            </td>
                            <td class="font-semibold tabular-nums">{{ $submission?->graded_at ? (float) $submission->score.'/'.$assignment->max_score : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="notebook-pen" :title="__('No assignments yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
