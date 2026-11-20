@extends('layouts.portal')

@section('title', __('Roles & Permissions'))

@section('content')
    <x-page-header :title="__('Roles & Permissions')" :subtitle="__('Decide what each administrator account can see and change. Administrators without a role have full access.')">
        <a href="{{ route('admin.delegations.index') }}" class="btn btn-outline"><x-lucide-key-round class="size-4" />{{ __('Delegations & Access') }}</a>
        @if (auth()->user()->canAdmin('users', 'manage'))
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><x-lucide-plus class="size-4" />{{ __('New role') }}</a>
        @endif
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-4 xl:col-span-2">
            @forelse ($roles as $role)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-navy">{{ $role->tr('name') }}</p>
                            @if ($role->description)<p class="text-xs text-muted">{{ $role->description }}</p>@endif
                        </div>
                        <span class="badge bg-brand-50 text-brand">{{ trans_choice(':count account|:count accounts', $role->users_count, ['count' => $role->users_count]) }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach (\App\Support\Permissions::MODULES as $module => $label)
                            @if ($role->allows($module, 'manage'))
                                <span class="badge bg-emerald-50 text-emerald-700" title="{{ __('Manage') }}">{{ __($label) }}</span>
                            @elseif ($role->allows($module))
                                <span class="badge bg-sky-50 text-sky-700" title="{{ __('View only') }}">{{ __($label) }}</span>
                            @endif
                        @endforeach
                    </div>
                    @if ($role->branch_scoped)<p class="mt-2 text-xs text-amber-700"><x-lucide-building-2 class="inline size-3.5" /> {{ __('Limited to the account\'s own branch') }}</p>@endif
                    @if (auth()->user()->canAdmin('users', 'manage'))
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-outline btn-sm"><x-lucide-pencil class="size-4" />{{ __('Edit') }}</a>
                            <x-confirm-delete :action="route('admin.roles.destroy', $role)" :label="__('Delete')" />
                        </div>
                    @endif
                </div>
            @empty
                <div class="card"><x-empty icon="shield-check" :title="__('No roles yet')" :message="__('Every administrator currently has full access.')" /></div>
            @endforelse
            <p class="flex items-center gap-3 text-xs text-muted">
                <span class="badge bg-emerald-50 text-emerald-700">{{ __('Manage') }}</span>
                <span class="badge bg-sky-50 text-sky-700">{{ __('View only') }}</span>
            </p>
        </div>

        <div class="card h-fit xl:col-span-3">
            <div class="card-header"><h2 class="card-title">{{ __('Administrator accounts') }}</h2></div>
            <ul class="divide-y divide-line">
                @foreach ($admins as $admin)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <img src="{{ $admin->avatarUrl() }}" alt="" class="size-10 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-navy">{{ $admin->name }} @if ($admin->is(auth()->user()))<span class="text-xs text-muted">({{ __('you') }})</span>@endif</p>
                                <p class="truncate text-xs text-muted">{{ $admin->email }} · {{ $admin->adminRole?->tr('name') ?? __('Full administrator') }}@if ($admin->branch) · {{ $admin->branch->tr('name') }}@endif</p>
                            </div>
                        </div>
                        @if (auth()->user()->canAdmin('users', 'manage') && ! $admin->is(auth()->user()) && (auth()->user()->isSuperAdmin() || ! $admin->isSuperAdmin()))
                            <form method="POST" action="{{ route('admin.roles.assign', $admin) }}" class="flex flex-wrap gap-2">
                                @csrf @method('PATCH')
                                <select name="admin_role_id" class="input w-44 py-1.5 text-sm" aria-label="{{ __('Role') }}">
                                    @if (auth()->user()->isSuperAdmin())<option value="">{{ __('Full administrator') }}</option>@endif
                                    @foreach ($assignable as $role)
                                        <option value="{{ $role->id }}" @selected($admin->admin_role_id === $role->id)>{{ $role->tr('name') }}</option>
                                    @endforeach
                                </select>
                                <select name="branch_id" class="input w-40 py-1.5 text-sm" aria-label="{{ __('Branch') }}">
                                    <option value="">{{ __('All branches') }}</option>
                                    @foreach (\App\Models\Branch::active()->get() as $branch)
                                        <option value="{{ $branch->id }}" @selected($admin->branch_id === $branch->id)>{{ $branch->tr('name') }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline btn-sm">{{ __('Save') }}</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
