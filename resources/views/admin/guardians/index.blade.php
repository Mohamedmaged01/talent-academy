@extends('layouts.portal')

@section('title', __('Parents'))

@section('content')
    <x-page-header :title="__('Parents & Guardians')" :subtitle="__('Parent accounts can follow their children’s attendance, grades and fees.')">
        <a href="{{ route('admin.guardians.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('Add Parent') }}</a>
    </x-page-header>

    <div class="card">
        <form method="GET" class="border-b border-line p-4">
            <div class="relative max-w-md">
                <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name, email or phone…') }}" class="input ps-9">
            </div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Parent') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Children') }}</th><th>{{ __('Last login') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($guardians as $guardian)
                        <tr>
                            <td class="font-semibold text-navy">{{ $guardian->name }}</td>
                            <td class="text-xs text-muted"><span dir="ltr" class="block">{{ $guardian->phone }}</span><span dir="ltr">{{ $guardian->email }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($guardian->children as $child)
                                        <a href="{{ route('admin.students.show', $child) }}" class="badge bg-brand-50 text-brand hover:bg-brand-100">{{ $child->full_name }}</a>
                                    @empty
                                        <span class="text-xs text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-xs text-muted">{{ $guardian->last_login_at?->diffForHumans() ?? __('Never') }}</td>
                            <td class="whitespace-nowrap text-end">
                                <a href="{{ route('admin.guardians.edit', $guardian) }}" class="btn btn-ghost btn-sm"><x-lucide-pencil class="size-4" /></a>
                                <x-confirm-delete :action="route('admin.guardians.destroy', $guardian)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="users" :title="__('No parent accounts yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $guardians->links() }}
    </div>
@endsection
