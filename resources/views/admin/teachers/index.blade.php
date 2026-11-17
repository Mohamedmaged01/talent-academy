@extends('layouts.portal')

@section('title', __('Teachers & Trainers'))

@section('content')
    <x-page-header :title="__('Teachers & Trainers')" :subtitle="__('Academic teachers and professional trainers.')">
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add Instructor') }}</a>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-col gap-3 p-4 sm:flex-row">
        <div class="relative flex-1">
            <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name, email or phone…') }}" class="input ps-9">
        </div>
        <select name="type" class="input sm:w-56" onchange="this.form.submit()">
            <option value="">{{ __('All types') }}</option>
            @foreach (\App\Models\TeacherProfile::TYPES as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __(\Illuminate\Support\Str::headline($type)) }}</option>
            @endforeach
        </select>
    </form>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($teachers as $teacher)
            <a href="{{ route('admin.teachers.show', $teacher) }}" class="card flex gap-4 p-5 transition hover:border-brand hover:shadow-md">
                <img src="{{ $teacher->avatarUrl() }}" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="truncate font-bold text-navy">{{ $teacher->name }}</p>
                        @unless ($teacher->is_active)<x-status value="inactive" />@endunless
                    </div>
                    <p class="truncate text-sm text-muted">{{ $teacher->teacherProfile?->tr('title') }}</p>
                    <p class="mt-1 truncate text-xs text-brand">{{ $teacher->teacherProfile?->tr('specialization') }}</p>
                    <div class="mt-3 flex items-center gap-4 text-xs text-muted">
                        <span class="inline-flex items-center gap-1"><x-lucide-users-round class="size-3.5" />{{ trans_choice(':count class|:count classes', $teacher->taught_batches_count) }}</span>
                        <span class="inline-flex items-center gap-1"><x-lucide-building-2 class="size-3.5" />{{ $teacher->branch?->tr('name') ?? '—' }}</span>
                    </div>
                </div>
            </a>
        @empty
            <x-empty icon="presentation" :title="__('No instructors found')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
    <div class="card mt-5">{{ $teachers->links() }}</div>
@endsection
