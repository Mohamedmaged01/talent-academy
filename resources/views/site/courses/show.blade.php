@extends('layouts.site')

@section('title', $course->tr('title'))
@section('description', $course->tr('description'))

@section('content')
    <section class="bg-gradient-to-l from-brand to-brand-dark text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-6 py-12 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <a href="{{ route('courses.index', ['pathway' => $course->pathway]) }}" class="inline-flex items-center gap-1 text-sm text-slate-200 hover:text-white">
                    <x-lucide-arrow-right class="size-4 ltr:rotate-180" /> {{ $course->pathway === 'academic' ? __('Academic Programs') : __('Training Programs') }}
                </a>
                <p class="mt-4 text-sm font-semibold text-gold">{{ $course->tr('category') ?: $course->tr('level') }}</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-snug md:text-4xl">{{ $course->tr('title') }}</h1>
                <p class="mt-4 leading-8 text-slate-100">{{ $course->tr('description') }}</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    @if ($course->tr('level'))<span class="rounded-lg bg-white/10 px-3 py-1.5">{{ $course->tr('level') }}</span>@endif
                    @if ($course->duration_weeks)<span class="rounded-lg bg-white/10 px-3 py-1.5">{{ trans_choice(':count week|:count weeks', $course->duration_weeks) }}</span>@endif
                    @if ($course->duration_hours)<span class="rounded-lg bg-white/10 px-3 py-1.5">{{ $course->duration_hours }} {{ __('training hours') }}</span>@endif
                    <span class="rounded-lg bg-white/10 px-3 py-1.5 font-latin" dir="ltr">{{ $course->code }}</span>
                </div>
            </div>
            <div class="lg:col-span-2">
                <div class="card overflow-hidden text-ink">
                    <img src="{{ $course->imageUrl() }}" alt="" class="aspect-[16/9] w-full object-cover">
                    <div class="p-5">
                        @if ((float) $course->price > 0)
                            <p class="text-sm text-muted">{{ __('Program fees') }}</p>
                            <p class="text-2xl font-extrabold text-navy">{{ money($course->price, false) }}</p>
                            <p class="text-xs text-muted">{{ __('Installment plans available') }}</p>
                        @endif
                        <a href="{{ route('admissions.create', ['course' => $course->id, 'pathway' => $course->pathway]) }}" class="btn btn-gold mt-4 w-full py-3 text-base">{{ __('Apply for this program') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-12">
        <h2 class="text-xl font-extrabold text-navy">{{ __('Open classes') }}</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @forelse ($course->batches as $batch)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-navy">{{ $batch->name }}</p>
                            <p class="text-sm text-muted">{{ $batch->branch->tr('name') }} · {{ $batch->room }}</p>
                        </div>
                        <x-status :value="$batch->status" />
                    </div>
                    <dl class="mt-4 grid gap-2 text-sm">
                        @if ($batch->teacher)
                            <div class="flex gap-2"><x-lucide-user-round class="size-4 text-brand" /><dd>{{ $batch->teacher->name }}</dd></div>
                        @endif
                        <div class="flex gap-2"><x-lucide-calendar-days class="size-4 text-brand" /><dd>{{ $batch->scheduleSummary() ?: __('Schedule to be announced') }}</dd></div>
                        @if ($batch->start_date)
                            <div class="flex gap-2"><x-lucide-flag class="size-4 text-brand" /><dd>{{ __('Starts') }} {{ $batch->start_date->translatedFormat('j F Y') }}</dd></div>
                        @endif
                    </dl>
                </div>
            @empty
                <div class="card p-6 text-sm text-muted md:col-span-2">{{ __('New classes are being scheduled. Apply now and we will contact you when the next class opens.') }}</div>
            @endforelse
        </div>

        @if ($related->isNotEmpty())
            <h2 class="mt-14 text-xl font-extrabold text-navy">{{ __('Related programs') }}</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('courses.show', $item) }}" class="card flex items-center gap-4 p-4 transition hover:border-brand">
                        <img src="{{ $item->imageUrl() }}" alt="" class="size-16 rounded-xl object-cover">
                        <span class="font-semibold leading-6 text-navy">{{ $item->tr('title') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
