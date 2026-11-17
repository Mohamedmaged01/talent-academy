@extends('layouts.portal')

@section('title', $batch->course->tr('title'))

@section('content')
    <x-page-header :title="$batch->course->tr('title')" :subtitle="$batch->name" :back="route('student.courses')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Course materials') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($batch->materials as $material)
                        <li>
                            <a href="{{ $material->file_path ? \Illuminate\Support\Facades\Storage::url($material->file_path) : $material->url }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand">
                                    <x-dynamic-component :component="match ($material->type) { 'video' => 'lucide-circle-play', 'link' => 'lucide-link', default => 'lucide-file-down' }" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-navy">{{ $material->title }}</span>
                                    <span class="block truncate text-xs text-muted">{{ $material->description ?: $material->original_name }} · {{ $material->created_at->translatedFormat('j M') }}</span>
                                </span>
                                <x-lucide-external-link class="size-4 text-muted" />
                            </a>
                        </li>
                    @empty
                        <li><x-empty icon="folder-open" :title="__('No materials shared yet')" /></li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Assignments') }}</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($assignments as $assignment)
                        @php $submission = $submissions[$assignment->id] ?? null; @endphp
                        <li>
                            <a href="{{ route('student.assignments.show', $assignment) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-navy">{{ $assignment->title }}</span>
                                    <span class="text-xs text-muted">{{ __('Due') }} {{ $assignment->due_at->translatedFormat('j M Y H:i') }}</span>
                                </span>
                                @if ($submission?->graded_at)
                                    <span class="badge bg-emerald-50 text-emerald-700">{{ (float) $submission->score }}/{{ $assignment->max_score }}</span>
                                @elseif ($submission)
                                    <x-status value="submitted" />
                                @else
                                    <x-status :value="$assignment->isOverdue() ? 'missing' : 'pending'" />
                                @endif
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">{{ __('No assignments yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <img src="{{ $batch->teacher?->avatarUrl() }}" alt="" class="size-12 rounded-xl object-cover">
                    <div>
                        <p class="font-bold text-navy">{{ $batch->teacher?->name ?? '—' }}</p>
                        <p class="text-xs text-muted">{{ $batch->teacher?->teacherProfile?->tr('title') }}</p>
                    </div>
                </div>
                @if ($batch->teacher?->teacherProfile?->office_hours)
                    <p class="mt-3 flex gap-2 text-xs text-muted"><x-lucide-clock class="size-4 shrink-0" />{{ $batch->teacher->teacherProfile->office_hours }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-stat :label="__('Attendance')" :value="pct($attendance)" icon="clipboard-check" />
                <x-stat :label="__('Average')" :value="pct($average)" icon="chart-column" tone="violet" />
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Schedule') }}</h2></div>
                <ul class="space-y-1.5 p-5 text-sm">
                    @foreach ($batch->schedules as $slot)
                        <li class="flex justify-between"><span>{{ $slot->dayName() }}</span><span class="font-latin text-muted" dir="ltr">{{ $slot->timeRange() }}</span></li>
                    @endforeach
                    <li class="flex justify-between border-t border-line pt-2"><span class="text-muted">{{ __('Room') }}</span><span>{{ $batch->room }}</span></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
