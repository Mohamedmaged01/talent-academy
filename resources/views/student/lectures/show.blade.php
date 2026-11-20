@extends('layouts.portal')

@section('title', $lecture->title)

@section('content')
    <x-page-header :title="$lecture->title" :subtitle="$lecture->batch->course->tr('title').' · '.$lecture->teacher?->name" :back="route('student.lectures')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <div class="card overflow-hidden">
                @if ($embed = $lecture->embedUrl())
                    <div class="aspect-video bg-navy">
                        <iframe src="{{ $embed }}" class="size-full" title="{{ $lecture->title }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
                    </div>
                @else
                    <div class="grid aspect-video place-items-center bg-gradient-to-br from-navy to-brand-dark p-6 text-center text-white">
                        <div>
                            <x-lucide-circle-play class="mx-auto size-14 text-gold" />
                            <p class="mt-3 text-sm text-slate-200">{{ __('This video opens on its own site.') }}</p>
                            <a href="{{ $lecture->video_url }}" target="_blank" rel="noopener" class="btn btn-gold mt-4"><x-lucide-external-link class="size-4" />{{ __('Watch video') }}</a>
                        </div>
                    </div>
                @endif
            </div>
            @if ($lecture->description)
                <div class="card card-body text-sm leading-7 whitespace-pre-line">{{ $lecture->description }}</div>
            @endif
        </div>

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('All lectures') }}</h2><span class="text-sm text-muted">{{ $playlist->count() }}</span></div>
            <ol class="divide-y divide-line">
                @foreach ($playlist as $item)
                    <li>
                        <a href="{{ route('student.lectures.show', $item) }}" @class(['flex items-center gap-3 px-5 py-3 text-sm hover:bg-canvas', 'bg-brand-50/60 font-semibold text-brand' => $item->id === $lecture->id])>
                            @if ($watched->has($item->id))
                                <x-lucide-circle-check class="size-4 shrink-0 text-emerald-600" />
                            @else
                                <x-lucide-circle class="size-4 shrink-0 text-slate-300" />
                            @endif
                            <span class="min-w-0 flex-1 truncate">{{ $loop->iteration }}. {{ $item->title }}</span>
                            @if ($item->duration_minutes)<span class="text-xs text-muted tabular-nums">{{ $item->duration_minutes }}′</span>@endif
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
@endsection
