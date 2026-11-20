@extends('layouts.portal')

@section('title', __('Payroll'))

@section('content')
    @php($manage = auth()->user()->canAdmin('payroll', 'manage'))
    <x-page-header :title="__('Payroll')" :subtitle="__('Monthly salaries for teachers and trainers. Paying a salary records it as an expense in the ledger.')">
        <a href="{{ route('admin.payroll.index', ['period' => $period, 'export' => 'csv']) }}" class="btn btn-outline"><x-lucide-file-spreadsheet class="size-4" />{{ __('Export CSV') }}</a>
    </x-page-header>

    <div class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <form method="GET" class="flex items-end gap-2">
            <x-input name="period" :label="__('Month')" type="month" :value="$period" />
            <button class="btn btn-outline">{{ __('Show') }}</button>
        </form>
        @if ($periods->isNotEmpty())
            <div class="flex flex-wrap gap-1">
                @foreach ($periods->take(6) as $p)
                    <a href="{{ route('admin.payroll.index', ['period' => $p]) }}" @class(['btn btn-sm', 'btn-primary' => $p === $period, 'btn-ghost' => $p !== $period]) dir="ltr">{{ $p }}</a>
                @endforeach
            </div>
        @endif
        @if ($manage && $missing > 0)
            <form method="POST" action="{{ route('admin.payroll.generate') }}" class="sm:ms-auto">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn btn-primary"><x-lucide-wand-sparkles class="size-4" />{{ trans_choice('Prepare :count salary|Prepare :count salaries', $missing, ['count' => $missing]) }}</button>
            </form>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Total net pay')" :value="money($totals['net'], false)" icon="banknote" />
        <x-stat :label="__('Paid')" :value="money($totals['paid'], false)" icon="circle-check" tone="emerald" />
        <x-stat :label="__('Waiting to be paid')" :value="money($totals['draft'], false)" icon="hourglass" tone="amber" />
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $period.'-01')->translatedFormat('F Y') }}</h2>
            @if ($manage && $totals['draft'] > 0)
                <form method="POST" action="{{ route('admin.payroll.pay-all') }}" class="flex items-center gap-2" onsubmit="return confirm(@js(__('Pay every waiting salary for this month?')))">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <select name="method" class="input py-1.5 text-xs" aria-label="{{ __('Payment method') }}">
                        @foreach (\App\Models\Payment::METHODS as $method)<option value="{{ $method }}">{{ __(\Illuminate\Support\Str::headline($method)) }}</option>@endforeach
                    </select>
                    <button class="btn btn-gold btn-sm"><x-lucide-check-check class="size-4" />{{ __('Pay all') }}</button>
                </form>
            @endif
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Staff member') }}</th><th>{{ __('Basic') }}</th><th>{{ __('Allowances') }}</th><th>{{ __('Bonus') }}</th><th>{{ __('Deductions') }}</th><th>{{ __('Net') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        @if ($record->isPaid() || ! $manage)
                            <tr>
                                <td><p class="font-semibold text-navy">{{ $record->user->name }}</p><p class="text-xs text-muted">{{ $record->user->teacherProfile?->tr('title') }}</p></td>
                                <td class="tabular-nums">{{ number_format($record->basic_salary, 2) }}</td>
                                <td class="tabular-nums">{{ number_format($record->allowances, 2) }}</td>
                                <td class="tabular-nums">{{ number_format($record->bonus, 2) }}</td>
                                <td class="tabular-nums text-rose-700">{{ number_format($record->deductions, 2) }}</td>
                                <td class="font-bold tabular-nums">{{ number_format($record->net(), 2) }}</td>
                                <td><x-status :value="$record->status" />@if ($record->paid_at)<p class="text-xs text-muted">{{ $record->paid_at->translatedFormat('j M') }}</p>@endif</td>
                                <td class="text-end"><a href="{{ route('admin.payroll.payslip', $record) }}" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Payslip') }}"><x-lucide-printer class="size-4" /></a></td>
                            </tr>
                        @else
                            <tr x-data="{ b: {{ (float) $record->basic_salary }}, a: {{ (float) $record->allowances }}, o: {{ (float) $record->bonus }}, d: {{ (float) $record->deductions }} }">
                                <td><p class="font-semibold text-navy">{{ $record->user->name }}</p><p class="text-xs text-muted">{{ $record->user->teacherProfile?->tr('title') }}</p></td>
                                @foreach (['basic_salary' => 'b', 'allowances' => 'a', 'bonus' => 'o', 'deductions' => 'd'] as $field => $var)
                                    <td><input form="pr-{{ $record->id }}" type="number" step="0.01" min="0" name="{{ $field }}" x-model.number="{{ $var }}" class="input w-28 py-1.5 tabular-nums" aria-label="{{ __(\Illuminate\Support\Str::headline($field)) }}"></td>
                                @endforeach
                                <td class="font-bold tabular-nums" x-text="(b + a + o - d).toFixed(2)"></td>
                                <td><x-status :value="$record->status" /></td>
                                <td class="text-end whitespace-nowrap">
                                    <form id="pr-{{ $record->id }}" method="POST" action="{{ route('admin.payroll.update', $record) }}" class="inline">
                                        @csrf @method('PUT')
                                        <button class="btn btn-ghost btn-sm" title="{{ __('Save') }}"><x-lucide-save class="size-4" /></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.payroll.pay', $record) }}" class="inline" onsubmit="return confirm(@js(__('Pay this salary now?')))">
                                        @csrf
                                        <input type="hidden" name="method" value="cash">
                                        <button class="btn btn-primary btn-sm"><x-lucide-banknote class="size-4" />{{ __('Pay') }}</button>
                                    </form>
                                    <x-confirm-delete :action="route('admin.payroll.destroy', $record)" />
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="8"><x-empty icon="banknote" :title="__('No payroll for this month yet')" :message="__('Prepare the month from each teacher\'s monthly salary with the button above.')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
