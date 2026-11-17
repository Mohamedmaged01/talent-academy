@extends('layouts.portal')

@section('title', __('Fees & Payments'))

@section('content')
    <x-page-header :title="__('Fees & Payments')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('Total fees')" :value="money($invoices->sum(fn ($i) => $i->total()), false)" icon="file-text" />
        <x-stat :label="__('Paid')" :value="money($payments->sum('amount'), false)" icon="circle-check" tone="emerald" />
        <x-stat :label="__('Balance due')" :value="money($balance, false)" icon="wallet" :tone="$balance > 0 ? 'rose' : 'emerald'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="card xl:col-span-3">
            <div class="card-header"><h2 class="card-title">{{ __('Invoices & installments') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($invoices as $invoice)
                    @php $progress = $invoice->total() > 0 ? min(100, round($invoice->paid_amount / $invoice->total() * 100)) : 0; @endphp
                    <li class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-navy">{{ $invoice->description }}</p>
                                <p class="text-xs text-muted"><span class="font-latin" dir="ltr">{{ $invoice->number }}</span> · {{ __('Due') }} {{ $invoice->due_date->translatedFormat('j M Y') }}</p>
                            </div>
                            <x-status :value="$invoice->status" />
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <div class="h-2 flex-1 rounded-full bg-line"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div></div>
                            <span class="text-xs tabular-nums text-muted">{{ money($invoice->paid_amount, false) }} / {{ money($invoice->total(), false) }}</span>
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="file-text" :title="__('No invoices')" /></li>
                @endforelse
            </ul>
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">{{ __('Payment history') }}</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($payments as $payment)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <span>
                            <span class="block text-sm font-semibold">{{ $payment->paid_at->translatedFormat('j M Y') }}</span>
                            <span class="block text-xs text-muted">{{ __(\Illuminate\Support\Str::headline($payment->method)) }} · <span class="font-latin" dir="ltr">{{ $payment->receipt_number }}</span></span>
                        </span>
                        <span class="font-bold tabular-nums text-emerald-700">{{ money($payment->amount, false) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-muted">{{ __('No payments yet.') }}</li>
                @endforelse
            </ul>
            <p class="border-t border-line px-5 py-3 text-xs text-muted">{{ __('To pay, visit the academy cashier or send a Vodafone Cash / InstaPay transfer and share the reference with the administration.') }}</p>
        </div>
    </div>
@endsection
