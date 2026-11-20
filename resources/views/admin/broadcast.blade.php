@extends('layouts.portal')

@section('title', __('Message a group'))

@section('content')
    <x-page-header :title="__('Message a group')" :subtitle="__('Everyone receives the message privately, and their replies come back to you only.')" :back="route('admin.announcements.index')" />

    <div class="grid gap-6 xl:grid-cols-3"
         x-data="{ audience: @js(old('audience', 'teachers')), subject: @js(old('subject', '')), body: @js(old('body', '')), save: {{ old('save_template') ? 'true' : 'false' }} }">
        <form method="POST" action="{{ route('admin.broadcast.store') }}" class="card xl:col-span-2">
            @csrf
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <div>
                    <label class="label" for="audience">{{ __('Send to') }}</label>
                    <select id="audience" name="audience" x-model="audience" class="input">
                        @foreach (\App\Http\Controllers\Admin\BroadcastController::AUDIENCES as $audience)
                            <option value="{{ $audience }}">{{ __('broadcast.'.$audience) }}</option>
                        @endforeach
                    </select>
                    @error('audience')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div x-show="audience.startsWith('batch_')" x-cloak>
                    <x-select name="batch_id" :label="__('Class')" :options="$batches->pluck('name', 'id')" :placeholder="__('Select…')" />
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="subject">{{ __('Subject') }} <span class="text-red-500">*</span></label>
                    <input id="subject" name="subject" x-model="subject" required class="input" dir="auto">
                    @error('subject')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="body">{{ __('Message') }} <span class="text-red-500">*</span></label>
                    <textarea id="body" name="body" x-model="body" rows="8" required class="input" dir="auto"></textarea>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="save_template" value="0">
                    <input type="checkbox" name="save_template" value="1" x-model="save"> {{ __('Save as a template') }}
                </label>
                <div x-show="save" x-cloak><x-input name="template_name" :label="__('Template name')" /></div>
            </div>
            <div class="flex justify-end border-t border-line px-6 py-4">
                <button class="btn btn-primary" onclick="return confirm(@js(__('Send this message to everyone in the group?')))"><x-lucide-send class="size-4 rtl:-scale-x-100" />{{ __('Send') }}</button>
            </div>
        </form>

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Templates') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($templates as $template)
                    <li class="flex items-center gap-2 px-5 py-3 text-sm">
                        <button type="button" class="min-w-0 flex-1 truncate text-start font-medium text-navy hover:text-brand"
                                @click="subject = @js($template->subject); body = @js($template->body)">{{ $template->name }}</button>
                        <x-confirm-delete :action="route('admin.templates.destroy', $template)" />
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-muted">{{ __('No templates yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
