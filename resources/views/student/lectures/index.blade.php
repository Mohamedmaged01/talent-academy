@extends('layouts.portal')

@section('title', __('Recorded Lectures'))

@section('content')
    <x-page-header :title="__('Recorded Lectures')" :subtitle="__('Watch your classes again, at your own pace.')" />

    <div class="space-y-6">
        @forelse ($batches as $batch)
            @php
                $items = $lectures[$batch->id];
                $seen = $items->filter(fn ($l) => $watched->has($l->id))->count();
            @endphp
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ $batch->course->tr('title') }}</h2>
                        <p class="text-xs text-muted">{{ $batch->name }}</p>
                    </div>
                    <div class="w-40 text-end">
                        <p class="text-xs text-muted">{{ __(':done of :total watched', ['done' => $seen, 'total' => $items->count()]) }}</p>
                        <div class="mt-1 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $items->count() ? round($seen / $items->count() * 100) : 0 }}%"></div></div>
                    </div>
                </div>
                <ul class="grid gap-px bg-line sm:grid-cols-2">
                    @foreach ($items as $lecture)
                        <li class="bg-white">
                            <a href="{{ route('student.lectures.show', $lecture) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-brand-50/40">
                                <span @class(['grid size-10 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600' => $watched->has($lecture->id), 'bg-brand-50 text-brand' => ! $watched->has($lecture->id)])>
                                    @if ($watched->has($lecture->id))<x-lucide-circle-check class="size-5" />@else<x-lucide-circle-play class="size-5" />@endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-navy">{{ $loop->iteration }}. {{ $lecture->title }}</span>
                                    <span class="text-xs text-muted">{{ $lecture->duration_minutes ? __(':count min', ['count' => $lecture->duration_minutes]) : __('Video') }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="card"><x-empty icon="clapperboard" :title="__('No recorded lectures yet')" :message="__('Your teachers have not shared any recordings yet.')" /></div>
        @endforelse
    </div>
@endsection
