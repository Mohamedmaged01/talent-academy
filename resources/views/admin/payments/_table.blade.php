<div class="table-wrap">
    <table class="table">
        <thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Student') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Method') }}</th><th>{{ __('Date') }}</th><th>{{ __('Amount') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td class="font-latin text-xs font-semibold" dir="ltr">{{ $payment->receipt_number }}</td>
                    <td>{{ $payment->student?->full_name }}</td>
                    <td><a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="font-latin text-xs text-brand" dir="ltr">{{ $payment->invoice?->number }}</a></td>
                    <td class="text-muted">{{ __(\Illuminate\Support\Str::headline($payment->method)) }}</td>
                    <td class="text-xs text-muted">{{ $payment->paid_at->translatedFormat('j M Y H:i') }}</td>
                    <td class="font-semibold tabular-nums text-emerald-700">{{ money($payment->amount, false) }}</td>
                    <td class="text-end"><a href="{{ route('admin.payments.receipt', $payment) }}" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Print receipt') }}"><x-lucide-printer class="size-4" /></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="wallet" :title="__('No payments')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
