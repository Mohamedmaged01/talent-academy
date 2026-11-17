@extends('layouts.portal')

@section('title', __('Branches'))

@section('content')
    <x-page-header :title="__('Branch Network')" :subtitle="__('Campuses, capacity and status.')">
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add Branch') }}</a>
    </x-page-header>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($branches as $branch)
            <div class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-latin text-xs font-semibold text-brand" dir="ltr">{{ $branch->code }}</p>
                        <h2 class="mt-1 text-lg font-bold text-navy">{{ $branch->tr('name') }}</h2>
                        <p class="text-sm text-muted">{{ $branch->city }}</p>
                    </div>
                    <x-status :value="$branch->status" />
                </div>
                <dl class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-canvas p-3 text-center">
                    <div><dt class="text-[11px] text-muted">{{ __('Students') }}</dt><dd class="font-bold text-navy">{{ $branch->students_count }}</dd></div>
                    <div><dt class="text-[11px] text-muted">{{ __('Classes') }}</dt><dd class="font-bold text-navy">{{ $branch->batches_count }}</dd></div>
                    <div><dt class="text-[11px] text-muted">{{ __('Capacity') }}</dt><dd class="font-bold text-navy">{{ $branch->capacity }}</dd></div>
                </dl>
                <ul class="mt-4 space-y-1.5 text-sm text-muted">
                    @if ($branch->manager_name)<li class="flex gap-2"><x-lucide-user-round class="size-4 shrink-0" />{{ $branch->manager_name }}</li>@endif
                    @if ($branch->phone)<li class="flex gap-2"><x-lucide-phone class="size-4 shrink-0" /><span dir="ltr">{{ $branch->phone }}</span></li>@endif
                    @if ($branch->address)<li class="flex gap-2"><x-lucide-map-pin class="size-4 shrink-0" />{{ $branch->address }}</li>@endif
                </ul>
                <div class="mt-auto flex gap-2 pt-5">
                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline btn-sm flex-1"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
                    @if ($branch->status !== 'archived')
                        <x-confirm-delete :action="route('admin.branches.destroy', $branch)" :message="__('Branches with linked records are archived, not deleted. Continue?')" icon="archive" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
