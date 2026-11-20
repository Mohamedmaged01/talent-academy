@extends('layouts.portal')

@section('title', __('My Payslips'))

@section('content')
    <x-page-header :title="__('My Payslips')" :subtitle="__('Salaries paid to you by the academy.')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Monthly salary')" :value="money($profile?->monthly_salary ?? 0, false)" icon="banknote" />
        <x-stat :label="__('Paid this year')" :value="money($records->filter(fn ($r) => str_starts_with($r->period, now()->format('Y')))->sum(fn ($r) => $r->net()), false)" icon="wallet" tone="emerald" />
        <x-stat :label="__('Payslips')" :value="$records->count()" icon="file-text" tone="violet" />
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('Month') }}</th><th>{{ __('Basic salary') }}</th><th>{{ __('Additions') }}</th><th>{{ __('Deductions') }}</th><th>{{ __('Net pay') }}</th><th>{{ __('Paid on') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="font-semibold text-navy">{{ $record->periodLabel() }}</td>
                            <td class="tabular-nums">{{ money($record->basic_salary, false) }}</td>
                            <td class="tabular-nums text-emerald-700">{{ money((float) $record->allowances + (float) $record->bonus, false) }}</td>
                            <td class="tabular-nums text-rose-700">{{ money($record->deductions, false) }}</td>
                            <td class="font-bold tabular-nums">{{ money($record->net(), false) }}</td>
                            <td class="text-xs text-muted">{{ $record->paid_at?->translatedFormat('j M Y') }}</td>
                            <td class="text-end"><a href="{{ route('teacher.payslips.show', $record) }}" target="_blank" class="btn btn-ghost btn-sm"><x-lucide-printer class="size-4" />{{ __('Payslip') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="banknote" :title="__('No payslips yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
