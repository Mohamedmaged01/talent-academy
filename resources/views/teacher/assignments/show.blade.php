@extends('layouts.portal')

@section('title', $assignment->title)

@section('content')
    <x-page-header :title="$assignment->title" :subtitle="$assignment->batch->name.' · '.__('Due').' '.$assignment->due_at->translatedFormat('j M Y H:i')" :back="route('teacher.assignments.index')">
        <x-confirm-delete :action="route('teacher.assignments.destroy', $assignment)" :label="__('Delete')" :message="__('Delete this assignment and all submissions?')" />
    </x-page-header>

    @if ($assignment->instructions || $assignment->attachment_path)
        <div class="card mb-6 p-5">
            <p class="whitespace-pre-line text-sm leading-7">{{ $assignment->instructions }}</p>
            @if ($assignment->attachment_path)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($assignment->attachment_path) }}" target="_blank" class="btn btn-outline btn-sm mt-3"><x-lucide-paperclip class="size-4" />{{ __('Attachment') }}</a>
            @endif
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ __('Submissions') }}</h2>
            <span class="text-sm text-muted">{{ $submissions->count() }} / {{ $students->count() }}</span>
        </div>
        <ul class="divide-y divide-line">
            @foreach ($students as $student)
                @php $submission = $submissions[$student->id] ?? null; @endphp
                <li class="px-5 py-4" x-data="{ open: false }">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex-1 font-semibold text-navy">{{ $student->full_name }}</span>
                        @if (! $submission)
                            <x-status :value="$assignment->isOverdue() ? 'missing' : 'pending'" />
                        @else
                            <span class="text-xs text-muted">{{ $submission->submitted_at->translatedFormat('j M H:i') }}</span>
                            @if ($submission->isLate())<x-status value="late" />@endif
                            @if ($submission->graded_at)
                                <span class="badge bg-emerald-50 text-emerald-700 tabular-nums">{{ (float) $submission->score }} / {{ $assignment->max_score }}</span>
                            @else
                                <x-status value="submitted" />
                            @endif
                            <button type="button" class="btn btn-outline btn-sm" @click="open = !open">{{ $submission->graded_at ? __('Edit grade') : __('Review & grade') }}</button>
                        @endif
                    </div>
                    @if ($submission)
                        <div x-show="open" x-collapse x-cloak class="mt-4 grid gap-4 rounded-xl bg-canvas p-4 lg:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold text-muted">{{ __("Student's answer") }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-7">{{ $submission->body ?: '—' }}</p>
                                @if ($submission->attachment_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($submission->attachment_path) }}" target="_blank" class="btn btn-outline btn-sm mt-2"><x-lucide-paperclip class="size-4" />{{ __('Open file') }}</a>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('teacher.submissions.grade', $submission) }}" class="grid gap-3">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="label">{{ __('Score') }} / {{ $assignment->max_score }}</label>
                                    <input type="number" name="score" step="0.5" min="0" max="{{ $assignment->max_score }}" value="{{ $submission->score !== null ? (float) $submission->score : '' }}" required class="input">
                                </div>
                                <div>
                                    <label class="label">{{ __('Feedback') }}</label>
                                    <textarea name="feedback" rows="3" class="input">{{ $submission->feedback }}</textarea>
                                </div>
                                <button class="btn btn-primary"><x-lucide-check class="size-4" />{{ __('Save grade') }}</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endsection
