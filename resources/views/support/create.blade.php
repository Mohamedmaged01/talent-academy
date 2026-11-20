@extends('layouts.portal')

@section('title', __('New ticket'))

@section('content')
    <x-page-header :title="__('New ticket')" :back="route('support.index')" />

    <form method="POST" action="{{ route('support.store') }}" class="card max-w-3xl">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-input name="subject" :label="__('Subject')" required class="sm:col-span-2" dir="auto" />
            <x-select name="category" :label="__('Category')" required :options="collect(\App\Models\SupportTicket::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __('ticket.'.$c)])" />
            <x-select name="priority" :label="__('Priority')" required value="normal" :options="collect(\App\Models\SupportTicket::PRIORITIES)->mapWithKeys(fn ($p) => [$p => __(\Illuminate\Support\Str::headline($p))])" />
            <x-input name="body" :label="__('Describe the problem')" type="textarea" rows="7" required class="sm:col-span-2" dir="auto" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('support.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Open ticket') }}</button>
        </div>
    </form>
@endsection
