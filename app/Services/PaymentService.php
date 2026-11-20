<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Record a payment against an invoice. Runs inside a transaction with a row
     * lock so two cashiers cannot over-collect the same invoice concurrently.
     */
    public function record(Invoice $invoice, float $amount, string $method, ?string $reference = null, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $notes) {
            /** @var Invoice $locked */
            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['amount' => __('This invoice is cancelled.')]);
            }

            $amount = round($amount, 2);
            $balance = $locked->balance();

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => __('Amount must be greater than zero.')]);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => __('Amount exceeds the remaining balance (:balance EGP).', ['balance' => number_format($balance, 2)]),
                ]);
            }

            $payment = $locked->payments()->create([
                'receipt_number' => Payment::nextReceiptNumber(),
                'student_id' => $locked->student_id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'paid_at' => now(),
                'received_by' => auth()->id(),
            ]);

            $locked->recalculate();

            ActivityLog::record('payment.recorded', "Payment {$payment->receipt_number} of {$amount} EGP on invoice {$locked->number}", $payment);

            Notify::student($locked->student, 'Payment received: :amount EGP (receipt :receipt)', ['amount' => number_format($amount, 2), 'receipt' => $payment->receipt_number],
                route('student.payments'), 'wallet', route('parent.children.show', $locked->student_id));

            return $payment;
        });
    }
}
