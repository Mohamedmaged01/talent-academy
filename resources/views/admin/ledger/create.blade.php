@extends('layouts.portal')

@section('title', __('Manual journal entry'))

@section('content')
    <x-page-header :title="__('Manual journal entry')" :subtitle="__('For adjustments, opening balances or anything not recorded elsewhere. Debits must equal credits.')" :back="route('admin.ledger.index')" />

    @php
        $lines = old('lines', [['account_id' => '', 'debit' => '', 'credit' => '', 'memo' => ''], ['account_id' => '', 'debit' => '', 'credit' => '', 'memo' => '']]);
    @endphp

    <form method="POST" action="{{ route('admin.ledger.store') }}" class="card"
          x-data="{
              lines: @js(array_values($lines)),
              get debit() { return this.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0) },
              get credit() { return this.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0) },
              get balanced() { return this.debit > 0 && Math.abs(this.debit - this.credit) < 0.005 },
          }">
        @csrf
        <div class="grid gap-4 p-6 sm:grid-cols-3">
            <x-input name="entry_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
            <x-select name="branch_id" :label="__('Branch')" :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])" :placeholder="__('Whole academy')" />
            <x-input name="description" :label="__('Description')" required class="sm:col-span-3" dir="auto" />
        </div>

        <div class="table-wrap border-t border-line">
            <table class="table">
                <thead><tr><th>{{ __('Account') }}</th><th class="w-36">{{ __('Debit') }}</th><th class="w-36">{{ __('Credit') }}</th><th>{{ __('Memo') }}</th><th class="w-10"></th></tr></thead>
                <tbody>
                    <template x-for="(line, i) in lines" :key="i">
                        <tr>
                            <td>
                                <select :name="`lines[${i}][account_id]`" x-model="line.account_id" class="input py-1.5" required aria-label="{{ __('Account') }}">
                                    <option value="">{{ __('Select…') }}</option>
                                    @foreach ($accounts->groupBy('type') as $type => $group)
                                        <optgroup label="{{ __('account.'.$type) }}">
                                            @foreach ($group as $account)
                                                <option value="{{ $account->id }}">{{ $account->label() }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" min="0" :name="`lines[${i}][debit]`" x-model="line.debit" @input="if (line.debit) line.credit = ''" class="input py-1.5 tabular-nums" aria-label="{{ __('Debit') }}"></td>
                            <td><input type="number" step="0.01" min="0" :name="`lines[${i}][credit]`" x-model="line.credit" @input="if (line.credit) line.debit = ''" class="input py-1.5 tabular-nums" aria-label="{{ __('Credit') }}"></td>
                            <td><input type="text" :name="`lines[${i}][memo]`" x-model="line.memo" class="input py-1.5" aria-label="{{ __('Memo') }}"></td>
                            <td><button type="button" class="btn btn-ghost btn-sm" @click="lines.splice(i, 1)" x-show="lines.length > 2" aria-label="{{ __('Remove') }}"><x-lucide-x class="size-4" /></button></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-line font-bold">
                        <td><button type="button" class="btn btn-ghost btn-sm" @click="lines.push({account_id: '', debit: '', credit: '', memo: ''})" x-show="lines.length < 20"><x-lucide-plus class="size-4" />{{ __('Add line') }}</button></td>
                        <td class="px-4 tabular-nums" x-text="debit.toFixed(2)"></td>
                        <td class="px-4 tabular-nums" x-text="credit.toFixed(2)"></td>
                        <td colspan="2" class="px-4">
                            <span x-show="balanced" class="badge bg-emerald-50 text-emerald-700"><x-lucide-check class="size-3" />{{ __('Balanced') }}</span>
                            <span x-show="! balanced" class="badge bg-rose-50 text-rose-700">{{ __('Difference') }}: <span class="tabular-nums" x-text="(debit - credit).toFixed(2)"></span></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @error('lines')<p class="field-error px-6">{{ $message }}</p>@enderror

        <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
            <a href="{{ route('admin.ledger.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
            <button class="btn btn-primary" :disabled="! balanced"><x-lucide-save class="size-4" />{{ __('Post entry') }}</button>
        </div>
    </form>
@endsection
