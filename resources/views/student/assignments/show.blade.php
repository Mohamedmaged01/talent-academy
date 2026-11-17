@extends('layouts.portal')

@section('title', $assignment->title)

@section('content')
    <x-page-header :title="$assignment->title" :subtitle="$assignment->batch->course->tr('title')" :back="route('student.assignments.index')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card p-6 xl:col-span-2">
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="inline-flex items-center gap-1.5 text-muted"><x-lucide-calendar-clock class="size-4" />{{ __('Due') }} <strong class="text-navy">{{ $assignment->due_at->translatedFormat('l j F Y — H:i') }}</strong></span>
                <span class="inline-flex items-center gap-1.5 text-muted"><x-lucide-target class="size-4" />{{ __('Maximum score') }} <strong class="text-navy">{{ $assignment->max_score }}</strong></span>
                <span class="inline-flex items-center gap-1.5 text-muted"><x-lucide-user-round class="size-4" />{{ $assignment->teacher?->name }}</span>
            </div>
            <div class="mt-5 whitespace-pre-line rounded-xl bg-canvas p-5 text-sm leading-7">{{ $assignment->instructions ?: __('No written instructions.') }}</div>
            @if ($assignment->attachment_path)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($assignment->attachment_path) }}" target="_blank" class="btn btn-outline btn-sm mt-4"><x-lucide-paperclip class="size-4" />{{ __('Download attachment') }}</a>
            @endif
        </div>

        <div class="space-y-6">
            @if ($submission?->graded_at)
                <div class="card border-emerald-200 p-6 text-center">
                    <p class="text-sm text-muted">{{ __('Your score') }}</p>
                    <p class="mt-1 text-4xl font-extrabold text-emerald-600 tabular-nums">{{ (float) $submission->score }}<span class="text-lg text-muted">/{{ $assignment->max_score }}</span></p>
                    @if ($submission->feedback)
                        <div class="mt-4 rounded-xl bg-canvas p-4 text-start text-sm"><p class="text-xs font-semibold text-muted">{{ __('Teacher feedback') }}</p><p class="mt-1 whitespace-pre-line">{{ $submission->feedback }}</p></div>
                    @endif
                </div>
            @endif

            @if ($submission)
                <div class="card p-5 text-sm">
                    <p class="flex items-center gap-2 font-semibold text-emerald-700"><x-lucide-circle-check class="size-4" />{{ __('Submitted :when', ['when' => $submission->submitted_at->diffForHumans()]) }}</p>
                    @if ($submission->isLate())<p class="mt-1 text-xs text-amber-700">{{ __('Submitted after the deadline.') }}</p>@endif
                    @if ($submission->body)<p class="mt-3 whitespace-pre-line text-muted">{{ \Illuminate\Support\Str::limit($submission->body, 300) }}</p>@endif
                    @if ($submission->attachment_path)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($submission->attachment_path) }}" target="_blank" class="btn btn-outline btn-sm mt-3"><x-lucide-paperclip class="size-4" />{{ __('My file') }}</a>
                    @endif
                </div>
            @endif

            @unless ($submission?->graded_at)
                <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">{{ $submission ? __('Update your submission') : __('Submit your work') }}</h2></div>
                    <div class="grid gap-4 p-5">
                        <x-input name="body" :label="__('Your answer')" type="textarea" rows="6" :value="$submission?->body" />
                        <div>
                            <label class="label" for="attachment">{{ __('Attach a file') }}</label>
                            <input id="attachment" type="file" name="attachment" class="input">
                            <p class="hint">{{ __('PDF, Word, PowerPoint, images or zip — up to 10 MB.') }}</p>
                            @error('attachment')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        @if ($assignment->isOverdue())
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ __('The deadline has passed. Your submission will be marked late.') }}</p>
                        @endif
                        <button class="btn btn-primary"><x-lucide-upload class="size-4" />{{ __('Submit') }}</button>
                    </div>
                </form>
            @endunless
        </div>
    </div>
@endsection
