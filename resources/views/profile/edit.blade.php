@extends('layouts.portal')

@section('title', __('My Profile'))

@section('content')
    <x-page-header :title="__('My Profile')" />

    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card">
            @csrf @method('PUT')
            <div class="card-header"><h2 class="card-title">{{ __('Personal information') }}</h2></div>
            <div class="grid gap-4 p-6">
                <div class="flex items-center gap-4">
                    <img src="{{ $user->avatarUrl() }}" alt="" class="size-20 rounded-2xl object-cover">
                    <div class="min-w-0 flex-1">
                        <label for="avatar" class="label">{{ __('Profile photo') }}</label>
                        <input id="avatar" type="file" name="avatar" accept="image/*" class="w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand">
                        @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-input name="name" :label="__('Full name')" :value="$user->name" required />
                <x-input name="email" :label="__('Email')" type="email" :value="$user->email" required dir="ltr" />
                <x-input name="phone" :label="__('Phone')" :value="$user->phone" dir="ltr" />
            </div>
            <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button></div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="card h-fit">
            @csrf @method('PUT')
            <div class="card-header"><h2 class="card-title">{{ __('Change password') }}</h2></div>
            <div class="grid gap-4 p-6">
                <x-input name="current_password" :label="__('Current password')" type="password" required bag="password" autocomplete="current-password" />
                <x-input name="password" :label="__('New password')" type="password" required bag="password" autocomplete="new-password" :hint="__('At least 8 characters.')" />
                <x-input name="password_confirmation" :label="__('Confirm new password')" type="password" required bag="password" autocomplete="new-password" />
            </div>
            <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><x-lucide-key-round class="size-4" />{{ __('Update password') }}</button></div>
        </form>
    </div>
@endsection
