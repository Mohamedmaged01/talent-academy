@extends('layouts.portal')

@section('title', $guardian->exists ? __('Edit Parent') : __('Add Parent'))

@section('content')
    <x-page-header :title="$guardian->exists ? __('Edit Parent') : __('Add Parent')" :back="route('admin.guardians.index')" />

    <form method="POST" action="{{ $guardian->exists ? route('admin.guardians.update', $guardian) : route('admin.guardians.store') }}" class="grid gap-6 xl:grid-cols-2"
          x-data="{ q: '' }">
        @csrf
        @if ($guardian->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Account') }}</h2></div>
            <div class="grid gap-4 p-6">
                <x-input name="name" :label="__('Full name')" :value="$guardian->name" required />
                <x-input name="email" :label="__('Login email')" type="email" :value="$guardian->email" required dir="ltr" />
                <x-input name="phone" :label="__('Phone')" :value="$guardian->phone" dir="ltr" />
                <x-input name="password" :label="$guardian->exists ? __('New password') : __('Password')" type="password" :required="! $guardian->exists" autocomplete="new-password" :hint="$guardian->exists ? __('Leave empty to keep the current password.') : __('At least 8 characters.')" />
                <x-select name="relationship" :label="__('Relationship')" :value="$relationship ?? 'father'" :options="['father' => __('Father'), 'mother' => __('Mother'), 'guardian' => __('Guardian')]" required />
            </div>
        </div>

        <div class="card flex flex-col">
            <div class="card-header"><h2 class="card-title">{{ __('Linked children') }}</h2></div>
            <div class="p-4">
                <input type="search" x-model="q" placeholder="{{ __('Filter students…') }}" class="input">
            </div>
            <div class="max-h-96 flex-1 overflow-y-auto px-4 pb-4">
                @php $selected = collect(old('students', $linked))->map(fn ($id) => (int) $id)->all(); @endphp
                @foreach ($students as $student)
                    <label class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm hover:bg-canvas"
                           x-show="!q || @js(mb_strtolower($student->full_name.' '.$student->code)).includes(q.toLowerCase())">
                        <input type="checkbox" name="students[]" value="{{ $student->id }}" @checked(in_array($student->id, $selected, true))>
                        <span class="flex-1">{{ $student->full_name }}</span>
                        <span class="font-latin text-xs text-muted" dir="ltr">{{ $student->code }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 xl:col-span-2">
            <a href="{{ route('admin.guardians.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
        </div>
    </form>
@endsection
