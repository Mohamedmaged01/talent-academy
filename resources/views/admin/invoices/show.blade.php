@extends('layouts.portal')

@section('title', $invoice->number)

@section('content')
    <x-page-header :title="__('Invoice').' '.$invoice->number" :subtitle="$invoice->student->full_name" :back="route('admin.invoices.index')">
        @if ($invoice->balance() > 0 && $invoice->status !== 'cancelled')
            @php
                $reminder = __('Hello :name, this is a friendly reminder from Talent Academy that :amount is due on invoice :number (due :date). Thank you.', [
                    'name' => $invoice->student->full_name, 'amount' => money($invoice->balance()), 'number' => $invoice->number, 'date' => $invoice->due_date->format('Y-m-d'),
                ]);
                $phone = $invoice->student->phone ?: $invoice->student->guardians->first()?->phone;
            @endphp
            @if ($wa = whatsapp_link($phone, $reminder))
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline"><x-lucide-message-circle class="size-4 text-emerald-600" />{{ __('WhatsApp reminder') }}</a>
            @endif
        @endif
        @if ($invoice->status !== 'cancelled' && $invoice->payments->isEmpty())
            <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" onsubmit="return confirm(@js(__('Cancel this invoice?')))">
                @csrf
                <button class="btn btn-danger-soft"><x-lucide-ban class="size-4" />{{ __('Cancel invoice') }}</button>
            </form>
        @endif
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Summary') }}</h2><x-status :value="$invoice->status" /></div>
                <div class="grid gap-4 p-6 sm:grid-cols-4">
                    <div><p class="text-xs text-muted">{{ __('Amount') }}</p><p class="text-lg font-bold tabular-nums">{{ money($invoice->amount, false) }}</p></div>
                    <div><p class="text-xs text-muted">{{ __('Discount') }}</p><p class="text-lg font-bold tabular-nums">{{ money($invoice->discount, false) }}</p></div>
                    <div><p class="text-xs text-muted">{{ __('Paid') }}</p><p class="text-lg font-bold tabular-nums text-emerald-700">{{ money($invoice->paid_amount, false) }}</p></div>
                    <div><p class="text-xs text-muted">{{ __('Balance') }}</p><p class="text-lg font-extrabold tabular-nums text-rose-600">{{ money($invoice->balance(), false) }}</p></div>
                </div>
                @php $progress = $invoice->total() > 0 ? min(100, round($invoice->paid_amount / $invoice->total() * 100)) : 0; @endphp
                <div class="px-6 pb-6"><div class="h-2 rounded-full bg-line"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div></div></div>
                <dl class="grid gap-4 border-t border-line p-6 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-muted">{{ __('Description') }}</dt><dd class="font-medium">{{ $invoice->description }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Student') }}</dt><dd><a href="{{ route('admin.students.show', $invoice->student) }}" class="font-medium text-brand">{{ $invoice->student->full_name }}</a> <span class="font-latin text-xs text-muted">{{ $invoice->student->code }}</span></dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Issue date') }}</dt><dd class="font-medium">{{ $invoice->issued_at->translatedFormat('j F Y') }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Due date') }}</dt><dd class="font-medium">{{ $invoice->due_date->translatedFormat('j F Y') }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Branch') }}</dt><dd class="font-medium">{{ $invoice->branch->tr('name') }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Class') }}</dt><dd class="font-medium">{{ $invoice->batch?->name ?? '—' }}</dd></div>
                    @if ($invoice->notes)<div class="sm:col-span-2"><dt class="text-xs text-muted">{{ __('Notes') }}</dt><dd>{{ $invoice->notes }}</dd></div>@endif
                </dl>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Payments') }}</h2></div>
                @include('admin.payments._table', ['payments' => $invoice->payments->each(fn ($p) => $p->setRelation('invoice', $invoice)->setRelation('student', $invoice->student))])
            </div>
        </div>

        <div>
            @if (in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true))
                <form method="POST" action="{{ route('admin.payments.store', $invoice) }}" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">{{ __('Record payment') }}</h2></div>
                    <div class="grid gap-4 p-5">
                        <x-input name="amount" :label="__('Amount (EGP)')" type="number" step="0.01" min="0.01" :max="$invoice->balance()" :value="$invoice->balance()" required
                                 :hint="__('Remaining balance: :amount', ['amount' => money($invoice->balance())])" />
                        <x-select name="method" :label="__('Payment method')" required :options="collect($methods)->mapWithKeys(fn ($m) => [$m => __(\Illuminate\Support\Str::headline($m))])" />
                        <x-input name="reference" :label="__('Transaction reference')" :hint="__('For Vodafone Cash, InstaPay or bank transfers.')" />
                        <x-input name="notes" :label="__('Notes')" />
                        <button class="btn btn-primary"><x-lucide-circle-check class="size-4" />{{ __('Record payment') }}</button>
                    </div>
                </form>
            @elseif ($invoice->status === 'paid')
                <div class="card border-emerald-200 bg-emerald-50 p-6 text-center text-emerald-800">
                    <x-lucide-badge-check class="mx-auto size-10" />
                    <p class="mt-2 font-bold">{{ __('Fully paid') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
