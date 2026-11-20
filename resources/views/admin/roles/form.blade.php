@extends('layouts.portal')

@section('title', $role->exists ? __('Edit role') : __('New role'))

@section('content')
    <x-page-header :title="$role->exists ? __('Edit role') : __('New role')" :back="route('admin.roles.index')" />

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="card max-w-4xl">
        @csrf
        @if ($role->exists) @method('PUT') @endif
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-input name="name_ar" :label="__('Name (Arabic)')" required :value="$role->name_ar" />
            <x-input name="name_en" :label="__('Name (English)')" required :value="$role->name_en" dir="ltr" />
            <x-input name="description" :label="__('Description')" :value="$role->description" class="sm:col-span-2" />
            <label class="flex items-start gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="branch_scoped" value="0">
                <input type="checkbox" name="branch_scoped" value="1" class="mt-1" @checked(old('branch_scoped', $role->branch_scoped))>
                <span><span class="font-semibold">{{ __('Limit to the account\'s own branch') }}</span><br><span class="text-xs text-muted">{{ __('Students, classes, invoices, payments, expenses and reports show only the branch set on the administrator\'s account.') }}</span></span>
            </label>
            <div class="sm:col-span-2">
                <p class="label">{{ __('Permissions') }}</p>
                @include('admin.roles._matrix', ['selected' => old('permissions', $role->permissions ?? []), 'holdable' => auth()->user()->adminPermissions()])
                @error('permissions')<p class="field-error">{{ $message }}</p>@enderror
                <p class="hint">{{ __('"Manage" includes "View". Greyed-out boxes are permissions you do not hold yourself.') }}</p>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
