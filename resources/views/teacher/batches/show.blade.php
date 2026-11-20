@extends('layouts.portal')

@section('title', $batch->name)

@section('content')
    <x-page-header :title="$batch->name" :subtitle="$batch->course->tr('title').' · '.$batch->room" :back="route('teacher.batches.index')">
        <a href="{{ route('teacher.attendance.edit', $batch) }}" class="btn btn-primary"><x-lucide-clipboard-check class="size-4" />{{ __('Take attendance') }}</a>
        <a href="{{ route('teacher.assignments.create', ['batch' => $batch->id]) }}" class="btn btn-outline"><x-lucide-notebook-pen class="size-4" />{{ __('Assignment') }}</a>
        <a href="{{ route('teacher.exams.create', ['batch' => $batch->id]) }}" class="btn btn-outline"><x-lucide-file-check class="size-4" />{{ __('Exam') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Class roster') }}</h2><span class="text-sm text-muted">{{ $students->count() }} / {{ $batch->capacity }}</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>{{ __('Student') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Attendance') }}</th><th>{{ __('Exam average') }}</th></tr></thead>
                    <tbody>
                        @forelse ($students as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td class="font-semibold text-navy">{{ $row['student']->full_name }}</td>
                                <td class="text-xs text-muted" dir="ltr">{{ $row['student']->phone }}</td>
                                <td>
                                    <span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['attendance'] !== null && $row['attendance'] < 80])>{{ pct($row['attendance']) }}</span>
                                </td>
                                <td><span @class(['font-semibold tabular-nums', 'text-rose-600' => $row['average'] !== null && $row['average'] < 70])>{{ pct($row['average']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="users" :title="__('No students enrolled yet')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Schedule') }}</h2></div>
                <ul class="space-y-1.5 p-5 text-sm">
                    @forelse ($batch->schedules as $slot)
                        <li class="flex justify-between"><span>{{ $slot->dayName() }}</span><span class="font-latin text-muted" dir="ltr">{{ $slot->timeRange() }}</span></li>
                    @empty
                        <li class="text-muted">{{ __('No sessions yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <form method="POST" action="{{ route('teacher.batches.meeting', $batch) }}" class="card">
                @csrf @method('PATCH')
                <div class="card-header">
                    <h2 class="card-title">{{ __('Live online sessions') }}</h2>
                    @if ($batch->meeting_url)
                        <a href="{{ $batch->meeting_url }}" target="_blank" rel="noopener" class="btn btn-gold btn-sm"><x-lucide-video class="size-4" />{{ __('Start') }}</a>
                    @endif
                </div>
                <div class="grid gap-3 p-5">
                    <x-input name="meeting_url" :label="__('Meeting link (Zoom, Meet, Teams)')" type="url" :value="$batch->meeting_url" dir="ltr" :hint="__('Students see a Join button 15 minutes before each scheduled session.')" />
                    <div class="flex flex-wrap gap-2">
                        <button class="btn btn-outline btn-sm"><x-lucide-save class="size-4" />{{ __('Save link') }}</button>
                        <a href="{{ route('teacher.lectures.index', ['batch' => $batch->id]) }}" class="btn btn-ghost btn-sm"><x-lucide-clapperboard class="size-4" />{{ __('Recorded lectures') }} ({{ $batch->lectures()->count() }})</a>
                    </div>
                </div>
            </form>

            <div class="card" x-data="{ type: @js(old('type', 'file')) }">
                <div class="card-header"><h2 class="card-title">{{ __('Course materials') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($batch->materials as $material)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                            <x-dynamic-component :component="match ($material->type) { 'video' => 'lucide-video', 'link' => 'lucide-link', default => 'lucide-file-text' }" class="size-4 shrink-0 text-brand" />
                            <a href="{{ $material->file_path ? \Illuminate\Support\Facades\Storage::url($material->file_path) : $material->url }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate hover:text-brand">{{ $material->title }}</a>
                            <x-confirm-delete :action="route('teacher.materials.destroy', $material)" />
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">{{ __('Nothing shared yet.') }}</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('teacher.materials.store', $batch) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-line p-5">
                    @csrf
                    <x-input name="title" :label="__('Title')" required />
                    <div>
                        <label class="label" for="type">{{ __('Type') }}</label>
                        <select id="type" name="type" x-model="type" class="input">
                            <option value="file">{{ __('File upload') }}</option>
                            <option value="video">{{ __('Video link') }}</option>
                            <option value="link">{{ __('Web link') }}</option>
                        </select>
                    </div>
                    <div x-show="type === 'file'">
                        <input type="file" name="file" class="input">
                        <p class="hint">{{ __('PDF, Office, images, zip or mp4 — up to 20 MB.') }}</p>
                        @error('file')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="type !== 'file'" x-cloak><x-input name="url" label="URL" type="url" dir="ltr" /></div>
                    <button class="btn btn-primary"><x-lucide-upload class="size-4" />{{ __('Share') }}</button>
                </form>
            </div>

            <form method="POST" action="{{ route('teacher.batches.announce', $batch) }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">{{ __('Message the class') }}</h2></div>
                <div class="grid gap-3 p-5">
                    <x-input name="title" :label="__('Title')" required />
                    <x-input name="body" :label="__('Message')" type="textarea" rows="3" required />
                    <button class="btn btn-outline"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
