@extends('layouts.portal')

@section('title', $teacher->name)

@section('content')
    <x-page-header :title="$teacher->name" :subtitle="$teacher->teacherProfile?->tr('title')" :back="route('admin.teachers.index')">
        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-primary"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card p-6 text-center">
            <img src="{{ $teacher->avatarUrl() }}" alt="" class="mx-auto size-24 rounded-3xl object-cover">
            <p class="mt-4 text-lg font-bold text-navy">{{ $teacher->name }}</p>
            <p class="text-sm text-muted">{{ __(\Illuminate\Support\Str::headline($teacher->teacherProfile?->instructor_type ?? 'academic_teacher')) }}</p>
            <div class="mt-2">@if ($teacher->is_active)<x-status value="active" />@else<x-status value="inactive" />@endif</div>
            <dl class="mt-6 divide-y divide-line text-start text-sm">
                @foreach ([
                    __('Specialization') => $teacher->teacherProfile?->tr('specialization'),
                    __('Email') => $teacher->email,
                    __('Phone') => $teacher->phone,
                    __('Home branch') => $teacher->branch?->tr('name'),
                    __('Hire date') => $teacher->teacherProfile?->hire_date?->translatedFormat('j F Y'),
                    __('Office hours') => $teacher->teacherProfile?->office_hours,
                    __('Monthly salary') => $teacher->teacherProfile ? money($teacher->teacherProfile->monthly_salary, false) : null,
                    __('Last login') => $teacher->last_login_at?->diffForHumans(),
                ] as $label => $value)
                    <div class="flex justify-between gap-3 py-2.5"><dt class="text-muted">{{ $label }}</dt><dd class="text-end font-medium">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            @if ($teacher->teacherProfile?->bio)
                <p class="mt-4 rounded-xl bg-canvas p-4 text-start text-sm leading-7 text-muted">{{ $teacher->teacherProfile->bio }}</p>
            @endif
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Assigned classes') }}</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Schedule') }}</th><th>{{ __('Students') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($teacher->taughtBatches as $batch)
                            <tr>
                                <td><a href="{{ route('admin.batches.show', $batch) }}" class="font-semibold text-navy hover:text-brand">{{ $batch->name }}</a><p class="text-xs text-muted">{{ $batch->course->tr('title') }}</p></td>
                                <td class="text-xs text-muted">{{ $batch->scheduleSummary() ?: '—' }}</td>
                                <td class="tabular-nums">{{ $batch->enrollments_count }}/{{ $batch->capacity }}</td>
                                <td><x-status :value="$batch->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="users-round" :title="__('No classes assigned')" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <x-confirm-delete :action="route('admin.teachers.destroy', $teacher)" :label="__('Delete instructor')" :message="__('Instructors with classes are deactivated instead of deleted. Continue?')" />
    </div>
@endsection
