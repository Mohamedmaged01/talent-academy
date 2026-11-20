@extends('layouts.portal')

@section('title', $account->label())

@section('content')
    <x-page-header :title="$account->label()" :subtitle="__('account.'.$account->type)" :back="route('admin.ledger.accounts')" />

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <x-input name="from" :label="__('From')" type="date" :value="$from->toDateString()" />
        <x-input name="to" :label="__('To')" type="date" :value="$to->toDateString()" />
        <button class="btn btn-primary"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button>
    </form>

    @php($sign = $account->isDebitNormal() ? 1 : -1)
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Entry') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
                <tbody>
                    <tr class="bg-canvas font-semibold">
                        <td colspan="5">{{ __('Opening balance') }}</td>
                        <td class="text-end tabular-nums">{{ number_format($opening * $sign, 2) }}</td>
                    </tr>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="text-xs whitespace-nowrap text-muted">{{ $row['line']->entry->entry_date->translatedFormat('j M Y') }}</td>
                            <td class="font-latin text-xs" dir="ltr">{{ $row['line']->entry->number }}</td>
                            <td class="text-sm">{{ $row['line']->entry->description }}</td>
                            <td class="text-end tabular-nums">{{ (float) $row['line']->debit ? number_format($row['line']->debit, 2) : '' }}</td>
                            <td class="text-end tabular-nums">{{ (float) $row['line']->credit ? number_format($row['line']->credit, 2) : '' }}</td>
                            <td class="text-end font-semibold tabular-nums">{{ number_format($row['balance'] * $sign, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="book-open-check" :title="__('No postings in this period')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
