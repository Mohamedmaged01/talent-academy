@extends('layouts.print')

@section('title', $payment->receipt_number)

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
                <p class="text-2xl font-extrabold text-navy">{{ __('Payment Receipt') }}</p>
                <p class="font-latin text-sm font-semibold text-brand" dir="ltr">{{ $payment->receipt_number }}</p>
            </div>
        </div>

        <dl class="mt-8 grid grid-cols-2 gap-x-8 gap-y-5 text-sm">
            <div><dt class="text-muted">{{ __('Received from') }}</dt><dd class="text-base font-bold">{{ $payment->student->full_name }}</dd></div>
            <div><dt class="text-muted">{{ __('Student code') }}</dt><dd class="font-latin font-semibold" dir="ltr">{{ $payment->student->code }}</dd></div>
            <div><dt class="text-muted">{{ __('Date') }}</dt><dd class="font-semibold">{{ $payment->paid_at->translatedFormat('j F Y — H:i') }}</dd></div>
            <div><dt class="text-muted">{{ __('Payment method') }}</dt><dd class="font-semibold">{{ __(\Illuminate\Support\Str::headline($payment->method)) }} @if ($payment->reference)<span class="font-latin text-muted" dir="ltr">({{ $payment->reference }})</span>@endif</dd></div>
            <div class="col-span-2"><dt class="text-muted">{{ __('For') }}</dt><dd class="font-semibold">{{ $payment->invoice->description }} — <span class="font-latin" dir="ltr">{{ $payment->invoice->number }}</span></dd></div>
        </dl>

        <div class="mt-8 flex items-center justify-between rounded-2xl bg-brand-50 p-6">
            <span class="font-semibold text-navy">{{ __('Amount received') }}</span>
            <span class="text-3xl font-extrabold text-brand tabular-nums">{{ money($payment->amount) }}</span>
        </div>
        <p class="mt-3 text-sm text-muted">{{ __('Remaining balance on invoice: :amount', ['amount' => money($payment->invoice->balance())]) }}</p>

        <div class="mt-16 flex items-end justify-between text-sm">
            <div><p class="text-muted">{{ __('Received by') }}</p><p class="mt-6 border-t border-ink pt-1 font-semibold">{{ $payment->receiver?->name }}</p></div>
            <p class="text-xs text-muted">{{ $payment->student->branch->tr('name') }}</p>
        </div>
    </div>
@endsection
