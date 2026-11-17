@extends('layouts.portal')

@section('title', __('My Courses'))

@section('content')
    <x-page-header :title="__('My Courses')" />

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $row)
            @php $batch = $row['batch']; @endphp
            <a href="{{ route('student.courses.show', $batch) }}" class="card flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
                <img src="{{ $batch->course->imageUrl() }}" alt="" class="aspect-[16/8] w-full object-cover">
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-center justify-between gap-2"><x-status :value="$batch->course->pathway" /><x-status :value="$batch->pivot->status" /></div>
                    <p class="mt-3 font-bold text-navy">{{ $batch->course->tr('title') }}</p>
                    <p class="text-sm text-muted">{{ $batch->teacher?->name }}</p>
                    <div class="mt-auto grid grid-cols-3 gap-2 pt-4 text-center text-xs">
                        <div class="rounded-lg bg-canvas p-2"><p class="text-muted">{{ __('Attendance') }}</p><p class="font-bold text-navy">{{ pct($row['attendance']) }}</p></div>
                        <div class="rounded-lg bg-canvas p-2"><p class="text-muted">{{ __('Average') }}</p><p class="font-bold text-navy">{{ pct($row['average']) }}</p></div>
                        <div class="rounded-lg bg-canvas p-2"><p class="text-muted">{{ __('Materials') }}</p><p class="font-bold text-navy">{{ $batch->materials_count }}</p></div>
                    </div>
                </div>
            </a>
        @empty
            <x-empty icon="book-open" :title="__('You are not enrolled in any course yet')" class="card md:col-span-2 xl:col-span-3" />
        @endforelse
    </div>
@endsection
