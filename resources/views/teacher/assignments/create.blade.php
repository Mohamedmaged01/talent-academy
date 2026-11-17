@extends('layouts.portal')

@section('title', __('New Assignment'))

@section('content')
    <x-page-header :title="__('New Assignment')" :back="route('teacher.assignments.index')" />

    <form method="POST" action="{{ route('teacher.assignments.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="batch_id" :label="__('Class')" :value="$selected" required class="sm:col-span-2" :options="$batches->pluck('name', 'id')" :placeholder="__('Select…')" />
            <x-input name="title" :label="__('Title')" required class="sm:col-span-2" />
            <x-input name="instructions" :label="__('Instructions')" type="textarea" rows="6" class="sm:col-span-2" />
            <x-input name="due_at" :label="__('Due date & time')" type="datetime-local" :value="now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i')" required />
            <x-input name="max_score" :label="__('Maximum score')" type="number" min="1" value="100" required />
            <div class="sm:col-span-2">
                <label for="attachment" class="label">{{ __('Attachment (optional)') }}</label>
                <input id="attachment" type="file" name="attachment" class="input">
                @error('attachment')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('teacher.assignments.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Publish') }}</button>
        </div>
    </form>
@endsection
