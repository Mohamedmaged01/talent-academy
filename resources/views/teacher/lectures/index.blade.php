@extends('layouts.portal')

@section('title', __('Recorded Lectures'))

@section('content')
    <x-page-header :title="__('Recorded Lectures')" :subtitle="__('Share session recordings with a class. YouTube, Vimeo and Google Drive links play inside the student portal.')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('teacher.lectures.store') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('Add a lecture') }}</h2></div>
            <div class="grid gap-4 p-5">
                <x-select name="batch_id" :label="__('Class')" required :value="$selected" :options="$batches->mapWithKeys(fn ($b) => [$b->id => $b->name])" :placeholder="__('Select…')" />
                <x-input name="title" :label="__('Title')" required />
                <x-input name="video_url" :label="__('Video link')" type="url" required dir="ltr" :hint="__('YouTube, Vimeo, Google Drive or any video page.')" />
                <x-input name="duration_minutes" :label="__('Duration (minutes)')" type="number" min="1" />
                <x-input name="description" :label="__('Description')" type="textarea" rows="3" />
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" checked> {{ __('Visible to students now') }}
                </label>
                <button class="btn btn-primary"><x-lucide-video class="size-4" />{{ __('Publish lecture') }}</button>
            </div>
        </form>

        <div class="space-y-6 xl:col-span-2">
            <form method="GET" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <select name="batch" class="input" onchange="this.form.submit()" aria-label="{{ __('Class') }}">
                    <option value="">{{ __('All my classes') }}</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected((string) $selected === (string) $batch->id)>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </form>

            @forelse ($lectures as $batchId => $items)
                @php $batch = $items->first()->batch; @endphp
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">{{ $batch->name }}</h2>
                            <p class="text-xs text-muted">{{ $batch->course->tr('title') }}</p>
                        </div>
                        <span class="text-sm text-muted">{{ trans_choice(':count lecture|:count lectures', $items->count(), ['count' => $items->count()]) }}</span>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($items as $lecture)
                            <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-sm font-bold text-brand tabular-nums">{{ $loop->iteration }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-navy">{{ $lecture->title }}</p>
                                    <p class="text-xs text-muted">
                                        @if ($lecture->duration_minutes){{ __(':count min', ['count' => $lecture->duration_minutes]) }} · @endif
                                        {{ __('Watched by :count of :total', ['count' => $lecture->views_count, 'total' => $enrolled[$batchId] ?? 0]) }}
                                        @unless ($lecture->is_published) · <span class="text-amber-600">{{ __('Hidden') }}</span>@endunless
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <form method="POST" action="{{ route('teacher.lectures.move', [$lecture, 'up']) }}">@csrf<button class="btn btn-ghost btn-sm" @disabled($loop->first) aria-label="{{ __('Move up') }}"><x-lucide-arrow-up class="size-4" /></button></form>
                                    <form method="POST" action="{{ route('teacher.lectures.move', [$lecture, 'down']) }}">@csrf<button class="btn btn-ghost btn-sm" @disabled($loop->last) aria-label="{{ __('Move down') }}"><x-lucide-arrow-down class="size-4" /></button></form>
                                    <a href="{{ $lecture->video_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="{{ __('Open') }}"><x-lucide-external-link class="size-4" /></a>
                                    <a href="{{ route('teacher.lectures.edit', $lecture) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}"><x-lucide-pencil class="size-4" /></a>
                                    <x-confirm-delete :action="route('teacher.lectures.destroy', $lecture)" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="card"><x-empty icon="video" :title="__('No lectures yet')" :message="__('Add your first recording with the form.')" /></div>
            @endforelse
        </div>
    </div>
@endsection
