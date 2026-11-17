@extends('layouts.portal')

@section('title', __('New Invoice'))

@section('content')
    <x-page-header :title="__('New Invoice')" :back="route('admin.invoices.index')" />

    <form method="POST" action="{{ route('admin.invoices.store') }}" class="card max-w-3xl"
          x-data="{ amount: {{ (float) old('amount', 0) }}, discount: {{ (float) old('discount', 0) }}, installments: {{ (int) old('installments', 1) }} }">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <x-select name="student_id" :label="__('Student')" :value="$selectedStudent" required class="sm:col-span-2"
                      :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->full_name.' — '.$s->code])" :placeholder="__('Select…')" />
            <x-select name="batch_id" :label="__('Related class (optional)')" class="sm:col-span-2"
                      :options="$batches->mapWithKeys(fn ($b) => [$b->id => $b->name.' — '.\Illuminate\Support\Str::limit($b->course->tr('title'), 40)])" :placeholder="__('None')" />
            <x-input name="description" :label="__('Description')" required class="sm:col-span-2" :hint="__('e.g. AI Diploma tuition — term 1')" />
            <div>
                <label for="amount" class="label">{{ __('Amount (EGP)') }} <span class="text-red-500">*</span></label>
                <input id="amount" name="amount" type="number" step="0.01" min="1" required x-model.number="amount" class="input">
                @error('amount')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="discount" class="label">{{ __('Discount (EGP)') }}</label>
                <input id="discount" name="discount" type="number" step="0.01" min="0" x-model.number="discount" class="input">
                @error('discount')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-input name="issued_at" :label="__('Issue date')" type="date" :value="now()->toDateString()" required />
            <x-input name="due_date" :label="__('Due date (first installment)')" type="date" :value="now()->addDays(14)->toDateString()" required />
            <div>
                <label for="installments" class="label">{{ __('Split into monthly installments') }}</label>
                <select id="installments" name="installments" x-model.number="installments" class="input">
                    @foreach (range(1, 12) as $n)
                        <option value="{{ $n }}">{{ $n === 1 ? __('Single payment') : trans_choice(':count installment|:count installments', $n) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rounded-xl bg-brand-50 p-4 text-sm">
                <p class="text-muted">{{ __('Net total') }}</p>
                <p class="text-xl font-extrabold text-navy tabular-nums" x-text="(Math.max(0, amount - discount)).toLocaleString(undefined, { minimumFractionDigits: 2 }) + ' EGP'"></p>
                <p class="text-xs text-muted" x-show="installments > 1" x-text="installments + ' × ' + (Math.max(0, amount - discount) / installments).toFixed(2)"></p>
            </div>
            <x-input name="notes" :label="__('Notes')" type="textarea" rows="2" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Issue Invoice') }}</button>
        </div>
    </form>
@endsection
