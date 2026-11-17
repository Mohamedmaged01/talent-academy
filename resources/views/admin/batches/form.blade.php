@extends('layouts.portal')

@section('title', $batch->exists ? __('Edit Class') : __('Create Class'))

@section('content')
    <x-page-header :title="$batch->exists ? __('Edit Class') : __('Create Class')" :back="$batch->exists ? route('admin.batches.show', $batch) : route('admin.batches.index')" />

    @php
        $initialSlots = old('schedules', $batch->exists
            ? $batch->schedules->map(fn ($s) => ['day_of_week' => $s->day_of_week, 'starts_at' => substr($s->starts_at, 0, 5), 'ends_at' => substr($s->ends_at, 0, 5)])->values()->all()
            : [['day_of_week' => 6, 'starts_at' => '16:00', 'ends_at' => '18:00']]);
    @endphp

    <form method="POST" action="{{ $batch->exists ? route('admin.batches.update', $batch) : route('admin.batches.store') }}" class="grid gap-6 xl:grid-cols-3"
          x-data="{ slots: @js(array_values($initialSlots)) }">
        @csrf
        @if ($batch->exists) @method('PUT') @endif

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Class details') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="name" :label="__('Class name')" :value="$batch->name" required class="sm:col-span-2" :hint="__('e.g. Grade 10A — Math (Group 1)')" />
                <x-input name="code" :label="__('Class code')" :value="$batch->code" required dir="ltr" />
                <x-select name="status" :label="__('Status')" :value="$batch->status" required :options="collect(\App\Models\Batch::STATUSES)->mapWithKeys(fn ($s) => [$s => __(ucfirst($s))])" />
                <x-select name="course_id" :label="__('Program')" :value="$batch->course_id" required class="sm:col-span-2"
                          :options="$courses->mapWithKeys(fn ($c) => [$c->id => ($c->pathway === 'academic' ? '🎓 ' : '💻 ').$c->tr('title')])" :placeholder="__('Select…')" />
                <x-select name="teacher_id" :label="__('Instructor')" :value="$batch->teacher_id" :options="$teachers->pluck('name', 'id')" :placeholder="__('Not assigned yet')" />
                <x-select name="branch_id" :label="__('Branch')" :value="$batch->branch_id" required :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" />
                <x-input name="room" :label="__('Room / lab / online link')" :value="$batch->room" />
                <x-input name="capacity" :label="__('Capacity')" type="number" min="1" :value="$batch->capacity" required />
                <x-input name="start_date" :label="__('Start date')" type="date" :value="$batch->start_date?->toDateString()" />
                <x-input name="end_date" :label="__('End date')" type="date" :value="$batch->end_date?->toDateString()" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Weekly schedule') }}</h2>
                    <button type="button" class="btn btn-ghost btn-sm" @click="slots.push({ day_of_week: 0, starts_at: '16:00', ends_at: '18:00' })"><x-lucide-plus class="size-4" />{{ __('Add') }}</button>
                </div>
                <div class="space-y-3 p-5">
                    <template x-for="(slot, i) in slots" :key="i">
                        <div class="grid grid-cols-[1fr_auto] gap-2 rounded-xl bg-canvas p-3">
                            <select class="input" :name="`schedules[${i}][day_of_week]`" x-model.number="slot.day_of_week">
                                @foreach ($days as $num => $day)
                                    <option value="{{ $num }}">{{ __($day) }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-danger-soft btn-sm row-span-2 self-center" @click="slots.splice(i, 1)" aria-label="{{ __('Remove') }}"><x-lucide-x class="size-4" /></button>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="time" class="input" :name="`schedules[${i}][starts_at]`" x-model="slot.starts_at" required>
                                <input type="time" class="input" :name="`schedules[${i}][ends_at]`" x-model="slot.ends_at" required>
                            </div>
                        </div>
                    </template>
                    <p x-show="slots.length === 0" class="text-center text-sm text-muted">{{ __('No sessions yet.') }}</p>
                    @foreach ($errors->get('schedules.*') as $messages)
                        @foreach ($messages as $message)<p class="field-error">{{ $message }}</p>@endforeach
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1"><x-lucide-save class="size-4" />{{ __('Save') }}</button>
                <a href="{{ route('admin.batches.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
