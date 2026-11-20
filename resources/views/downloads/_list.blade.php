{{-- Downloads grouped by category. Expects $downloads: Collection<category, Collection<Download>> --}}
<div class="space-y-6">
    @forelse ($downloads as $category => $files)
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('downloads.'.$category) }}</h2><span class="text-sm text-muted">{{ $files->count() }}</span></div>
            <ul class="divide-y divide-line">
                @foreach ($files as $file)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold/20 font-latin text-[10px] font-extrabold text-navy">{{ $file->extension() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-navy">{{ $file->tr('title') }}</p>
                            @if ($file->tr('description'))<p class="text-xs text-muted">{{ $file->tr('description') }}</p>@endif
                        </div>
                        @if ($file->humanSize())<span class="text-xs text-muted tabular-nums" dir="ltr">{{ $file->humanSize() }}</span>@endif
                        <a href="{{ route('downloads.open', $file) }}" class="btn btn-outline btn-sm" @if (! $file->file_path) target="_blank" rel="noopener" @endif>
                            @if ($file->file_path)<x-lucide-download class="size-4" />{{ __('Download') }}@else<x-lucide-external-link class="size-4" />{{ __('Open') }}@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="card"><x-empty icon="download" :title="__('No files to download yet')" /></div>
    @endforelse
</div>
