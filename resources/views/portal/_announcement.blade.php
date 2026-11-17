<article @class(['card p-5', 'border-s-4 border-s-gold' => $announcement->is_pinned])>
    <div class="flex items-start gap-3">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand">
            <x-dynamic-component :component="$announcement->is_pinned ? 'lucide-pin' : 'lucide-megaphone'" class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="font-bold text-navy">{{ $announcement->title }}</p>
            <p class="text-xs text-muted">
                {{ $announcement->author?->name ?? __('Administration') }} · {{ $announcement->created_at->diffForHumans() }}
                @if ($announcement->batch) · <span class="text-brand">{{ $announcement->batch->name }}</span>@endif
            </p>
            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-ink">{{ $announcement->body }}</p>
        </div>
    </div>
</article>
