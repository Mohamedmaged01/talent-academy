@extends('layouts.portal')

@section('title', $branch->exists ? __('Edit Branch') : __('Add Branch'))

@section('content')
    <x-page-header :title="$branch->exists ? __('Edit Branch') : __('Add Branch')" :back="route('admin.branches.index')" />

    <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card max-w-3xl">
        @csrf
        @if ($branch->exists) @method('PUT') @endif
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-input name="code" :label="__('Branch code')" :value="$branch->code" required dir="ltr" />
            <x-select name="status" :label="__('Status')" :value="$branch->status" :options="['active' => __('Active'), 'inactive' => __('Inactive'), 'archived' => __('Archived')]" required />
            <x-input name="name_ar" :label="__('Name (Arabic)')" :value="$branch->name_ar" required dir="rtl" />
            <x-input name="name_en" :label="__('Name (English)')" :value="$branch->name_en" required dir="ltr" />
            <x-input name="city" :label="__('City')" :value="$branch->city" />
            <x-input name="capacity" :label="__('Student capacity')" type="number" min="0" :value="$branch->capacity" required />
            <x-input name="address" :label="__('Address')" :value="$branch->address" class="sm:col-span-2" />
            <x-input name="phone" :label="__('Phone')" :value="$branch->phone" dir="ltr" />
            <x-input name="email" :label="__('Email')" type="email" :value="$branch->email" dir="ltr" />
            <x-input name="manager_name" :label="__('Branch manager')" :value="$branch->manager_name" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
