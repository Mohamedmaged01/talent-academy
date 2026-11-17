@extends('layouts.portal')

@section('title', __('Classes & Groups'))

@section('content')
    <x-page-header :title="__('Classes & Groups')" :subtitle="__('Each class links a program, an instructor, a room and a weekly schedule.')">
        <a href="{{ route('admin.batches.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Create Class') }}</a>
    </x-page-header>

    <form method="GET" class="card mb-5 grid gap-3 p-4 md:grid-cols-4">
        <div class="relative">
            <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name or code…') }}" class="input ps-9">
        </div>
        <select name="status" class="input" onchange="this.form.submit()">
            <option value="">{{ __('All statuses') }}</option>
            @foreach (\App\Models\Batch::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
            @endforeach
        </select>
        <select name="pathway" class="input" onchange="this.form.submit()">
            <option value="">{{ __('All pathways') }}</option>
            <option value="academic" @selected(request('pathway') === 'academic')>{{ __('Academic') }}</option>
            <option value="training" @selected(request('pathway') === 'training')>{{ __('Training') }}</option>
        </select>
        <select name="branch" class="input" onchange="this.form.submit()">
            <option value="">{{ __('All branches') }}</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>{{ $branch->tr('name') }}</option>
            @endforeach
        </select>
    </form>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            @php $fill = $batch->capacity ? min(100, round($batch->enrollments_count / $batch->capacity * 100)) : 0; @endphp
            <a href="{{ route('admin.batches.show', $batch) }}" class="card flex flex-col p-5 transition hover:border-brand hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <x-status :value="$batch->course->pathway" />
                    <x-status :value="$batch->status" />
                </div>
                <p class="mt-3 font-bold text-navy">{{ $batch->name }}</p>
                <p class="text-sm text-muted">{{ \Illuminate\Support\Str::limit($batch->course->tr('title'), 55) }}</p>
                <ul class="mt-4 space-y-1.5 text-xs text-muted">
                    <li class="flex gap-2"><x-lucide-user-round class="size-3.5 shrink-0" />{{ $batch->teacher?->name ?? __('No instructor') }}</li>
                    <li class="flex gap-2"><x-lucide-map-pin class="size-3.5 shrink-0" />{{ $batch->branch->tr('name') }} · {{ $batch->room }}</li>
                    <li class="flex gap-2"><x-lucide-calendar-days class="size-3.5 shrink-0" />{{ $batch->scheduleSummary() ?: '—' }}</li>
                </ul>
                <div class="mt-auto pt-4">
                    <div class="flex justify-between text-xs"><span class="text-muted">{{ __('Seats') }}</span><span class="font-semibold tabular-nums">{{ $batch->enrollments_count }}/{{ $batch->capacity }}</span></div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-line"><div @class(['h-1.5 rounded-full', 'bg-rose-500' => $fill >= 100, 'bg-brand' => $fill < 100]) style="width: {{ $fill }}%"></div></div>
                </div>
            </a>
        @empty
            <x-empty icon="users-round" :title="__('No classes found')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
    <div class="card mt-5">{{ $batches->links() }}</div>
@endsection
