@extends('layouts.portal')

@section('title', __('User Accounts'))

@section('content')
    <x-page-header :title="__('User Accounts')" :subtitle="__('Every portal login. Deactivating an account signs the user out immediately.')" />

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index') }}" @class(['btn btn-sm', 'btn-primary' => ! request('role'), 'btn-outline' => request('role')])>{{ __('All') }} <span class="opacity-70">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\User::ROLES as $role)
            <a href="{{ route('admin.users.index', ['role' => $role]) }}" @class(['btn btn-sm', 'btn-primary' => request('role') === $role, 'btn-outline' => request('role') !== $role])>{{ __('role.'.$role) }} <span class="opacity-70">{{ $counts[$role] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="border-b border-line p-4">
                <input type="hidden" name="role" value="{{ request('role') }}">
                <div class="relative max-w-md">
                    <x-lucide-search class="pointer-events-none absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name or email…') }}" class="input ps-9">
                </div>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('User') }}</th><th>{{ __('Role') }}</th><th>{{ __('Last login') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr x-data="{ reset: false }">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $user->avatarUrl() }}" alt="" class="size-9 rounded-full object-cover">
                                        <div><p class="font-semibold text-navy">{{ $user->name }}</p><p class="text-xs text-muted" dir="ltr">{{ $user->email }}</p></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-brand-50 text-brand">{{ __('role.'.$user->role) }}</span></td>
                                <td class="text-xs text-muted">{{ $user->last_login_at?->diffForHumans() ?? __('Never') }}</td>
                                <td>@if ($user->is_active)<x-status value="active" />@else<x-status value="inactive" />@endif</td>
                                <td class="whitespace-nowrap text-end">
                                    <button type="button" class="btn btn-ghost btn-sm" @click="reset = !reset" title="{{ __('Reset password') }}"><x-lucide-key-round class="size-4" /></button>
                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm {{ $user->is_active ? 'btn-danger-soft' : 'btn-outline' }}" title="{{ $user->is_active ? __('Deactivate') : __('Activate') }}">
                                                <x-dynamic-component :component="$user->is_active ? 'lucide-user-x' : 'lucide-user-check'" class="size-4" />
                                            </button>
                                        </form>
                                    @endunless
                                    <form x-show="reset" x-cloak method="POST" action="{{ route('admin.users.password', $user) }}" class="mt-2 flex gap-2">
                                        @csrf @method('PATCH')
                                        <input type="password" name="password" required minlength="8" placeholder="{{ __('New password') }}" class="input py-1.5 text-xs" autocomplete="new-password">
                                        <button class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('New administrator') }}</h2></div>
            <div class="grid gap-4 p-5">
                <p class="text-xs text-muted">{{ __('Students, teachers and parents get their accounts from their own sections.') }}</p>
                <x-input name="name" :label="__('Full name')" required />
                <x-input name="email" :label="__('Email')" type="email" required dir="ltr" />
                <x-input name="phone" :label="__('Phone')" dir="ltr" />
                <x-select name="branch_id" :label="__('Branch')" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('All branches')" />
                <x-input name="password" :label="__('Password')" type="password" required autocomplete="new-password" />
                <button class="btn btn-primary"><x-lucide-shield-plus class="size-4" />{{ __('Create account') }}</button>
            </div>
        </form>
    </div>
@endsection
