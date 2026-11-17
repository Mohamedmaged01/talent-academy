@extends('layouts.portal')

@section('title', __('My Children'))

@section('content')
    <x-page-header :title="__('Welcome, :name', ['name' => auth()->user()->name])" :subtitle="__('Follow your children’s attendance, grades and fees in one place.')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            @forelse ($children as $row)
                @php $child = $row['student']; @endphp
                <a href="{{ route('parent.children.show', $child) }}" class="card block p-6 transition hover:border-brand hover:shadow-md">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="grid size-14 place-items-center rounded-2xl bg-brand text-xl font-bold text-white">{{ mb_substr($child->full_name, 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-bold text-navy">{{ $child->displayName() }}</p>
                            <p class="text-sm text-muted">{{ $child->grade_level }} · {{ $child->branch->tr('name') }}</p>
                        </div>
                        <span class="btn btn-outline btn-sm hidden sm:inline-flex">{{ __('Details') }} <x-lucide-chevron-left class="size-4 ltr:rotate-180" /></span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="rounded-xl bg-canvas p-3"><p class="text-xs text-muted">{{ __('Attendance') }}</p><p @class(['text-xl font-extrabold', 'text-rose-600' => $row['attendance'] !== null && $row['attendance'] < 80, 'text-navy' => $row['attendance'] === null || $row['attendance'] >= 80])>{{ pct($row['attendance']) }}</p></div>
                        <div class="rounded-xl bg-canvas p-3"><p class="text-xs text-muted">{{ __('Exam average') }}</p><p class="text-xl font-extrabold text-navy">{{ pct($row['average']) }}</p></div>
                        <div class="rounded-xl bg-canvas p-3"><p class="text-xs text-muted">{{ __('Absences (30 days)') }}</p><p @class(['text-xl font-extrabold', 'text-rose-600' => $row['absences'] > 2, 'text-navy' => $row['absences'] <= 2])>{{ $row['absences'] }}</p></div>
                        <div class="rounded-xl bg-canvas p-3"><p class="text-xs text-muted">{{ __('Balance due') }}</p><p @class(['text-xl font-extrabold', 'text-rose-600' => $row['balance'] > 0, 'text-emerald-600' => $row['balance'] <= 0])>{{ money($row['balance'], false) }}</p></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($child->batches as $batch)
                            <span class="badge bg-brand-50 text-brand">{{ \Illuminate\Support\Str::limit($batch->course->tr('title'), 40) }}</span>
                        @endforeach
                    </div>
                </a>
            @empty
                <div class="card"><x-empty icon="users" :title="__('No children linked to your account')" :message="__('Please contact the academy administration to link your children.')" /></div>
            @endforelse
        </div>

        <div class="space-y-3">
            <h2 class="font-bold text-navy">{{ __('Announcements') }}</h2>
            @forelse ($announcements as $announcement)
                @include('portal._announcement', ['announcement' => $announcement])
            @empty
                <div class="card p-5 text-sm text-muted">{{ __('No announcements yet') }}</div>
            @endforelse
        </div>
    </div>
@endsection
