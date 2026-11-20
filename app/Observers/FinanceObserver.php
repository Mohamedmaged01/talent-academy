<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\LedgerService;
use App\Support\Webhooks;

/**
 * Posts invoices, payments and expenses to the double-entry ledger as they
 * happen, and announces invoices and payments to webhooks.
 */
class FinanceObserver
{
    public function __construct(private LedgerService $ledger) {}

    public function created(Invoice|Payment|Expense $model): void
    {
        match (true) {
            $model instanceof Invoice => $this->invoiceCreated($model),
            $model instanceof Payment => $this->paymentCreated($model),
            $model instanceof Expense => $this->ledger->expenseRecorded($model),
        };
    }

    public function updated(Invoice|Payment|Expense $model): void
    {
        if ($model instanceof Invoice && $model->wasChanged('status') && $model->status === 'cancelled') {
            $this->ledger->invoiceCancelled($model);
        }
    }

    public function deleted(Invoice|Payment|Expense $model): void
    {
        if ($model instanceof Expense) {
            $this->ledger->expenseRemoved($model);
        }
    }

    private function invoiceCreated(Invoice $invoice): void
    {
        $this->ledger->invoiceIssued($invoice);

        if ($invoice->status === 'cancelled') {
            $this->ledger->invoiceCancelled($invoice);
        }

        Webhooks::dispatch('invoice.created', [
            'number' => $invoice->number,
            'student_code' => $invoice->student?->code,
            'description' => $invoice->description,
            'total' => $invoice->total(),
            'due_date' => $invoice->due_date?->toDateString(),
        ]);
    }

    private function paymentCreated(Payment $payment): void
    {
        $this->ledger->paymentReceived($payment);

        Webhooks::dispatch('payment.received', [
            'receipt_number' => $payment->receipt_number,
            'invoice_number' => $payment->invoice?->number,
            'student_code' => $payment->student?->code,
            'amount' => (float) $payment->amount,
            'method' => $payment->method,
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ]);
    }
}
