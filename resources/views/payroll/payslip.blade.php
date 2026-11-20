@extends('layouts.print')

@section('title', __('Payslip').' '.$record->period)

@section('content')
    <div class="mx-auto max-w-2xl bg-white p-10 shadow-sm print:shadow-none">
        <div class="flex items-start justify-between border-b-2 border-brand pb-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.jpg') }}" alt="" class="size-16 object-contain">
                <div dir="ltr">
                    <p class="font-latin text-xl font-extrabold text-navy">TALENT ACADEMY</p>
                    <p class="font-latin text-xs text-brand">For Educational Services</p>
                </div>
            </div>
            <div class="text-end">
                <p class="text-2xl font-extrabold text-navy">{{ __('Payslip') }}</p>
                <p class="text-sm font-semibold text-brand">{{ $record->periodLabel() }}</p>
            </div>
        </div>

        <dl class="mt-8 grid grid-cols-2 gap-x-8 gap-y-5 text-sm">
            <div><dt class="text-muted">{{ __('Staff member') }}</dt><dd class="text-base font-bold">{{ $record->user->name }}</dd></div>
            <div><dt class="text-muted">{{ __('Position') }}</dt><dd class="font-semibold">{{ $record->user->teacherProfile?->tr('title') ?? '—' }}</dd></div>
            <div><dt class="text-muted">{{ __('Branch') }}</dt><dd class="font-semibold">{{ $record->branch?->tr('name') ?? '—' }}</dd></div>
            <div><dt class="text-muted">{{ __('Paid on') }}</dt><dd class="font-semibold">{{ $record->paid_at?->translatedFormat('j F Y') ?? __('Not paid yet') }} @if ($record->method)· {{ __(\Illuminate\Support\Str::headline($record->method)) }}@endif</dd></div>
        </dl>

        <table class="mt-8 w-full text-sm">
            <tbody class="divide-y divide-line">
                <tr><td class="py-2.5">{{ __('Basic salary') }}</td><td class="py-2.5 text-end tabular-nums">{{ money($record->basic_salary) }}</td></tr>
                <tr><td class="py-2.5">{{ __('Allowances') }}</td><td class="py-2.5 text-end tabular-nums">{{ money($record->allowances) }}</td></tr>
                <tr><td class="py-2.5">{{ __('Bonus') }}</td><td class="py-2.5 text-end tabular-nums">{{ money($record->bonus) }}</td></tr>
                <tr><td class="py-2.5 text-rose-700">{{ __('Deductions') }}</td><td class="py-2.5 text-end tabular-nums text-rose-700">− {{ money($record->deductions) }}</td></tr>
            </tbody>
        </table>

        <div class="mt-6 flex items-center justify-between rounded-2xl bg-brand-50 p-6">
            <span class="font-semibold text-navy">{{ __('Net pay') }}</span>
            <span class="text-3xl font-extrabold text-brand tabular-nums">{{ money($record->net()) }}</span>
        </div>

        @if ($record->notes)<p class="mt-4 text-sm text-muted">{{ $record->notes }}</p>@endif

        <div class="mt-12 flex items-end justify-between text-xs text-muted">
            <p>{{ __('Approved by') }}: {{ $record->payer?->name ?? '—' }}</p>
            <button onclick="window.print()" class="btn btn-primary print:hidden"><x-lucide-printer class="size-4" />{{ __('Print') }}</button>
        </div>
    </div>
@endsection
