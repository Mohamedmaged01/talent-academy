@extends('layouts.portal')

@section('title', __('My Classes'))

@section('content')
    <x-page-header :title="__('My Classes')" :subtitle="__('Classes assigned to you.')" />

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            <div class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-2">
                    <x-status :value="$batch->course->pathway" />
                    <x-status :value="$batch->status" />
                </div>
                <a href="{{ route('teacher.batches.show', $batch) }}" class="mt-3 font-bold text-navy hover:text-brand">{{ $batch->name }}</a>
                <p class="text-sm text-muted">{{ $batch->course->tr('title') }}</p>
                <ul class="mt-4 space-y-1.5 text-xs text-muted">
                    <li class="flex gap-2"><x-lucide-users class="size-3.5" />{{ $batch->enrollments_count }} {{ __('students') }}</li>
                    <li class="flex gap-2"><x-lucide-map-pin class="size-3.5" />{{ $batch->branch->tr('name') }} · {{ $batch->room }}</li>
                    <li class="flex gap-2"><x-lucide-calendar-days class="size-3.5" />{{ $batch->scheduleSummary() ?: '—' }}</li>
                </ul>
                <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
                    <a href="{{ route('teacher.attendance.edit', $batch) }}" class="btn btn-primary btn-sm"><x-lucide-clipboard-check class="size-4" />{{ __('Attendance') }}</a>
                    <a href="{{ route('teacher.batches.show', $batch) }}" class="btn btn-outline btn-sm">{{ __('Open class') }}</a>
                </div>
            </div>
        @empty
            <x-empty icon="users-round" :title="__('No classes assigned yet')" :message="__('The administration will assign classes to you.')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
@endsection
