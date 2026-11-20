@extends('layouts.portal')

@section('title', __('Trial balance'))

@section('content')
    <x-page-header :title="__('Trial balance')" :subtitle="__('As of :date', ['date' => $asOf->translatedFormat('j F Y')])">
        <a href="{{ route('admin.ledger.trial-balance', ['as_of' => $asOf->toDateString(), 'export' => 'csv']) }}" class="btn btn-outline"><x-lucide-file-spreadsheet class="size-4" />{{ __('Export CSV') }}</a>
    </x-page-header>

    @include('admin.ledger._tabs')

    @php $gaps = array_sum($missing); @endphp
    @if ($gaps > 0)
        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-start gap-2"><x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" />{{ __(':count records are not in the ledger yet (invoices :i, payments :p, expenses :e). This is expected right after upgrading.', ['count' => $gaps, 'i' => $missing['invoices'], 'p' => $missing['payments'], 'e' => $missing['expenses']]) }}</p>
            @if (auth()->user()->canAdmin('ledger', 'manage'))
                <x-confirm-delete :action="route('admin.ledger.rebuild')" method="POST" icon="refresh-cw" :label="__('Rebuild ledger')"
                                  :message="__('Re-post every invoice, payment and expense? Manual entries are kept.')" />
            @endif
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="flex items-end gap-3 border-b border-line p-4">
                <x-input name="as_of" :label="__('As of')" type="date" :value="$asOf->toDateString()" />
                <button class="btn btn-primary"><x-lucide-filter class="size-4" />{{ __('Apply') }}</button>
                <span class="ms-auto">
                    @if (abs($totals['debit'] - $totals['credit']) < 0.005)
                        <span class="badge bg-emerald-50 text-emerald-700"><x-lucide-scale class="size-3" />{{ __('Balanced') }}</span>
                    @else
                        <span class="badge bg-rose-50 text-rose-700"><x-lucide-triangle-alert class="size-3" />{{ __('Out of balance') }}</span>
                    @endif
                </span>
            </form>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Debit balance') }}</th><th class="text-end">{{ __('Credit balance') }}</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a href="{{ route('admin.ledger.accounts.show', $row['account']) }}" class="hover:text-brand">{{ $row['account']->label() }}</a></td>
                                <td class="text-xs text-muted">{{ __('account.'.$row['account']->type) }}</td>
                                <td class="text-end tabular-nums">{{ $row['balance'] > 0 ? number_format($row['balance'], 2) : '' }}</td>
                                <td class="text-end tabular-nums">{{ $row['balance'] < 0 ? number_format(-$row['balance'], 2) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="scale" :title="__('No postings yet')" /></td></tr>
                        @endforelse
                    </tbody>
                    @php
                        $debitBalances = $rows->sum(fn ($r) => max(0, $r['balance']));
                        $creditBalances = $rows->sum(fn ($r) => max(0, -$r['balance']));
                    @endphp
                    <tfoot>
                        <tr class="border-t-2 border-navy font-bold">
                            <td colspan="2" class="px-4 py-3">{{ __('Total') }}</td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ number_format($debitBalances, 2) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ number_format($creditBalances, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card h-fit">
            <div class="card-header">
                <h2 class="card-title">{{ __('Income statement') }}</h2>
                <span class="text-xs text-muted">{{ $start->translatedFormat('j M') }} – {{ $asOf->translatedFormat('j M Y') }}</span>
            </div>
            <div class="space-y-4 p-5 text-sm">
                <div>
                    <p class="mb-1 font-semibold text-navy">{{ __('Revenue') }}</p>
                    @foreach ($income['revenue'] as $row)
                        <div class="flex justify-between py-0.5"><span class="text-muted">{{ $row['account']->tr('name') }}</span><span class="tabular-nums">{{ number_format($row['amount'], 2) }}</span></div>
                    @endforeach
                    <div class="mt-1 flex justify-between border-t border-line pt-1 font-semibold"><span>{{ __('Total revenue') }}</span><span class="tabular-nums">{{ number_format($income['total_revenue'], 2) }}</span></div>
                </div>
                <div>
                    <p class="mb-1 font-semibold text-navy">{{ __('Expenses') }}</p>
                    @foreach ($income['expenses'] as $row)
                        @if ($row['amount'] != 0)
                            <div class="flex justify-between py-0.5"><span class="text-muted">{{ $row['account']->tr('name') }}</span><span class="tabular-nums">{{ number_format($row['amount'], 2) }}</span></div>
                        @endif
                    @endforeach
                    <div class="mt-1 flex justify-between border-t border-line pt-1 font-semibold"><span>{{ __('Total expenses') }}</span><span class="tabular-nums">{{ number_format($income['total_expenses'], 2) }}</span></div>
                </div>
                <div @class(['flex justify-between rounded-xl p-3 text-base font-extrabold', 'bg-emerald-50 text-emerald-800' => $income['net'] >= 0, 'bg-rose-50 text-rose-800' => $income['net'] < 0])>
                    <span>{{ $income['net'] >= 0 ? __('Net profit') : __('Net loss') }}</span><span class="tabular-nums">{{ money(abs($income['net'])) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
