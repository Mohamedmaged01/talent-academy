@extends('layouts.portal')

@section('title', __('Live Classes'))

@section('content')
    <x-page-header :title="__('Live Classes')" :subtitle="__('Join online sessions from here. The Join button opens 15 minutes before class.')" />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($rows as $row)
            @php($batch = $row['batch'])
            <div @class(['card flex flex-col p-5', 'ring-2 ring-emerald-400' => $row['live']])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-navy">{{ $batch->course->tr('title') }}</p>
                        <p class="text-xs text-muted">{{ $batch->name }} · {{ $batch->teacher?->name }}</p>
                    </div>
                    @if ($row['live'])
                        <span class="badge bg-emerald-50 text-emerald-700"><span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>{{ __('Live now') }}</span>
                    @elseif ($batch->meeting_url)
                        <span class="badge bg-sky-50 text-sky-700">{{ __('Online') }}</span>
                    @else
                        <span class="badge bg-slate-100 text-slate-600">{{ __('In person') }}</span>
                    @endif
                </div>

                <div class="mt-4 flex-1 rounded-xl bg-canvas p-3 text-sm">
                    @if ($row['session'])
                        <p class="text-xs text-muted">{{ __('Next session') }}</p>
                        <p class="font-semibold text-navy">{{ $row['session']['starts']->translatedFormat('l j M') }}</p>
                        <p class="font-latin text-muted" dir="ltr">{{ $row['session']['starts']->format('H:i') }} – {{ $row['session']['ends']->format('H:i') }}</p>
                    @else
                        <p class="text-muted">{{ __('No sessions scheduled.') }}</p>
                    @endif
                    @if ($batch->room)<p class="mt-2 text-xs text-muted"><x-lucide-map-pin class="inline size-3.5" /> {{ $batch->room }}</p>@endif
                </div>

                @if ($batch->meeting_url)
                    @if ($row['live'])
                        <a href="{{ $batch->meeting_url }}" target="_blank" rel="noopener" class="btn btn-primary mt-4"><x-lucide-video class="size-4" />{{ __('Join session') }}</a>
                    @else
                        <button class="btn btn-outline mt-4" disabled><x-lucide-clock class="size-4" />{{ __('Opens 15 min before class') }}</button>
                    @endif
                @endif
            </div>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-empty icon="video" :title="__('No active classes')" /></div>
        @endforelse
    </div>
@endsection
