@extends('layouts.portal')

@section('title', __('Announcements'))

@section('content')
    <x-page-header :title="__('Announcements')" :subtitle="__('Messages shown inside the student, parent and teacher portals.')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="card h-fit" x-data="{ audience: @js(old('audience', 'all')) }">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('New announcement') }}</h2></div>
            <div class="grid gap-4 p-5">
                <x-input name="title" :label="__('Title')" required />
                <x-input name="body" :label="__('Message')" type="textarea" rows="5" required />
                <div>
                    <label for="audience" class="label">{{ __('Audience') }}</label>
                    <select id="audience" name="audience" x-model="audience" class="input">
                        @foreach (\App\Models\Announcement::AUDIENCES as $audience)
                            <option value="{{ $audience }}">{{ __('audience.'.$audience) }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="audience === 'batch'" x-collapse>
                    <x-select name="batch_id" :label="__('Class')" :options="$batches->pluck('name', 'id')" :placeholder="__('Select…')" />
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_pinned" value="1"> {{ __('Pin to top') }}</label>
                <button class="btn btn-primary"><x-lucide-megaphone class="size-4" />{{ __('Publish') }}</button>
            </div>
        </form>

        <div class="space-y-4 xl:col-span-2">
            @forelse ($announcements as $announcement)
                <article class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="flex items-center gap-2 font-bold text-navy">
                                @if ($announcement->is_pinned)<x-lucide-pin class="size-4 text-gold" />@endif
                                {{ $announcement->title }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $announcement->author?->name ?? __('System') }} · {{ $announcement->created_at->diffForHumans() }} ·
                                <span class="font-semibold text-brand">{{ $announcement->audience === 'batch' ? $announcement->batch?->name : __('audience.'.$announcement->audience) }}</span>
                            </p>
                        </div>
                        <x-confirm-delete :action="route('admin.announcements.destroy', $announcement)" />
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm leading-7">{{ $announcement->body }}</p>
                </article>
            @empty
                <div class="card"><x-empty icon="megaphone" :title="__('No announcements yet')" /></div>
            @endforelse
            <div class="card">{{ $announcements->links() }}</div>
        </div>
    </div>
@endsection
