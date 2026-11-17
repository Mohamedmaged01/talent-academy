@extends('layouts.portal')

@section('title', $admission->full_name)

@section('content')
    <x-page-header :title="$admission->full_name" :subtitle="__('Application').' '.$admission->reference" :back="route('admin.admissions.index')" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Application details') }}</h2><x-status :value="$admission->status" /></div>
            <dl class="grid gap-x-6 gap-y-4 p-6 text-sm sm:grid-cols-2">
                @foreach ([
                    __('Pathway') => __(ucfirst($admission->pathway)),
                    __('Program') => $admission->course?->tr('title') ?? __('Not sure yet — advise me'),
                    __('Mobile number') => $admission->phone,
                    __('Email') => $admission->email,
                    __('Current grade / level') => $admission->grade_level,
                    __('Preferred branch') => $admission->branch?->tr('name') ?? __('Any branch'),
                    __('Guardian name') => $admission->guardian_name,
                    __('Guardian mobile') => $admission->guardian_phone,
                    __('Received') => $admission->created_at->translatedFormat('j F Y — H:i'),
                    __('Reviewed by') => $admission->reviewer ? $admission->reviewer->name.' · '.$admission->reviewed_at?->diffForHumans() : null,
                ] as $label => $value)
                    <div><dt class="text-xs text-muted">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value ?: '—' }}</dd></div>
                @endforeach
                @if ($admission->notes)
                    <div class="sm:col-span-2"><dt class="text-xs text-muted">{{ __('Notes') }}</dt><dd class="mt-1 whitespace-pre-line rounded-xl bg-canvas p-3">{{ $admission->notes }}</dd></div>
                @endif
            </dl>
            <div class="flex flex-wrap gap-2 border-t border-line px-6 py-4">
                <a href="tel:{{ $admission->phone }}" class="btn btn-outline btn-sm"><x-lucide-phone class="size-4" />{{ __('Call') }}</a>
                <a href="https://wa.me/{{ preg_replace('/^0/', '20', preg_replace('/\D/', '', $admission->phone)) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-lucide-message-circle class="size-4" />WhatsApp</a>
            </div>
        </div>

        <div class="space-y-6">
            @if ($admission->student)
                <div class="card border-emerald-200 bg-emerald-50 p-5">
                    <p class="flex items-center gap-2 font-bold text-emerald-800"><x-lucide-circle-check class="size-5" />{{ __('Accepted') }}</p>
                    <p class="mt-1 text-sm text-emerald-800">{{ __('Student record created.') }}</p>
                    <a href="{{ route('admin.students.show', $admission->student) }}" class="btn btn-primary btn-sm mt-3">{{ __('Open student record') }}</a>
                </div>
            @else
                <form method="POST" action="{{ route('admin.admissions.convert', $admission) }}" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">{{ __('Accept & enroll') }}</h2></div>
                    <div class="grid gap-4 p-5">
                        <x-select name="branch_id" :label="__('Branch')" :value="$admission->branch_id" required :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" />
                        <x-select name="batch_id" :label="__('Enroll in class (optional)')" :options="$batches->mapWithKeys(fn ($b) => [$b->id => $b->name.' — '.\Illuminate\Support\Str::limit($b->course->tr('title'), 30)])" :placeholder="__('Later')" />
                        <button class="btn btn-primary"><x-lucide-user-check class="size-4" />{{ __('Accept and create student') }}</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.admissions.update', $admission) }}" class="card">
                    @csrf @method('PATCH')
                    <div class="card-header"><h2 class="card-title">{{ __('Update status') }}</h2></div>
                    <div class="grid gap-4 p-5">
                        <x-select name="status" :label="__('Status')" :value="$admission->status" :options="['pending' => __('Pending'), 'contacted' => __('Contacted'), 'rejected' => __('Rejected')]" />
                        <x-input name="notes" :label="__('Notes')" type="textarea" rows="3" :value="$admission->notes" />
                        <button class="btn btn-outline"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
