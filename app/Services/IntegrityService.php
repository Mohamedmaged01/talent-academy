<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\PayrollRecord;

/**
 * Live consistency checks across finance, enrollment and certificates. Each
 * check reports pass/fail with a count and a few examples to follow up.
 */
class IntegrityService
{
    /** @return list<array{key: string, title: string, description: string, ok: bool, count: int, examples: list<array{label: string, url: ?string}>}> */
    public function run(): array
    {
        return [
            $this->trialBalance(),
            $this->invoicePaidAmounts(),
            $this->overpaidInvoices(),
            $this->ledgerCoverage(),
            $this->batchCapacity(),
            $this->certificateRules(),
            $this->payrollExpenses(),
        ];
    }

    private function check(string $key, string $title, string $description, int $count, array $examples = []): array
    {
        return ['key' => $key, 'title' => $title, 'description' => $description, 'ok' => $count === 0, 'count' => $count, 'examples' => array_slice($examples, 0, 5)];
    }

    private function trialBalance(): array
    {
        $debit = round((float) JournalLine::sum('debit'), 2);
        $credit = round((float) JournalLine::sum('credit'), 2);
        $diff = abs($debit - $credit) > 0.004 ? 1 : 0;

        return $this->check('trial_balance', __('Ledger balances'), __('Total debits equal total credits (:debit / :credit).', [
            'debit' => number_format($debit, 2), 'credit' => number_format($credit, 2),
        ]), $diff, $diff ? [['label' => __('Open the trial balance'), 'url' => route('admin.ledger.trial-balance')]] : []);
    }

    private function invoicePaidAmounts(): array
    {
        $bad = Invoice::withSum('payments', 'amount')->where('status', '!=', 'cancelled')->get()
            ->filter(fn (Invoice $i) => abs((float) $i->paid_amount - (float) $i->payments_sum_amount) > 0.004);

        return $this->check('invoice_paid', __('Invoice payments add up'), __("Each invoice's paid amount equals the sum of its receipts."), $bad->count(),
            $bad->map(fn ($i) => ['label' => $i->number, 'url' => route('admin.invoices.show', $i)])->values()->all());
    }

    private function overpaidInvoices(): array
    {
        $bad = Invoice::where('status', '!=', 'cancelled')->get()->filter(fn (Invoice $i) => (float) $i->paid_amount - $i->total() > 0.004);

        return $this->check('overpaid', __('No invoice is over-collected'), __('Nothing was collected beyond an invoice total.'), $bad->count(),
            $bad->map(fn ($i) => ['label' => $i->number, 'url' => route('admin.invoices.show', $i)])->values()->all());
    }

    private function ledgerCoverage(): array
    {
        $invoices = Invoice::whereDoesntHave('journalEntries')->get()->filter(fn (Invoice $i) => $i->total() > 0);
        $payments = Payment::whereDoesntHave('journalEntries')->get();
        $expenses = Expense::whereDoesntHave('journalEntries')->get();
        $count = $invoices->count() + $payments->count() + $expenses->count();

        return $this->check('ledger_coverage', __('Everything is in the ledger'), __('Every invoice, payment and expense has its journal entry. Fix gaps with "Rebuild ledger".'), $count,
            $invoices->map(fn ($i) => ['label' => $i->number, 'url' => route('admin.invoices.show', $i)])
                ->concat($payments->map(fn ($p) => ['label' => $p->receipt_number, 'url' => route('admin.payments.receipt', $p)]))
                ->concat($expenses->map(fn ($e) => ['label' => __('Expense').' #'.$e->id, 'url' => route('admin.expenses.index')]))
                ->values()->all());
    }

    private function batchCapacity(): array
    {
        $bad = Batch::whereIn('status', ['active', 'upcoming'])->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])->get()
            ->filter(fn (Batch $b) => $b->enrollments_count > $b->capacity);

        return $this->check('capacity', __('Classes within capacity'), __('No active class has more students than seats.'), $bad->count(),
            $bad->map(fn ($b) => ['label' => "{$b->name} ({$b->enrollments_count}/{$b->capacity})", 'url' => route('admin.batches.show', $b)])->values()->all());
    }

    private function certificateRules(): array
    {
        $bad = Certificate::with('student')->where('status', 'valid')->whereNotNull('batch_id')->get()->filter(function (Certificate $c) {
            $attendance = $c->student->attendanceRate($c->batch_id);
            $average = $c->student->averageExamPercent($c->batch_id);

            return ($attendance !== null && $attendance < CertificateService::MIN_ATTENDANCE) || ($average !== null && $average < CertificateService::MIN_AVERAGE);
        });

        return $this->check('certificates', __('Certificates meet the rules'), __('Every valid class certificate belongs to a student with ≥ 80% attendance and ≥ 70% average.'), $bad->count(),
            $bad->map(fn ($c) => ['label' => $c->serial.' · '.$c->student->full_name, 'url' => route('admin.certificates.show', $c)])->values()->all());
    }

    private function payrollExpenses(): array
    {
        $bad = PayrollRecord::with('user')->where('status', 'paid')->whereNull('expense_id')->get();

        return $this->check('payroll', __('Paid salaries are booked'), __('Every paid payroll record has its salaries expense.'), $bad->count(),
            $bad->map(fn ($r) => ['label' => $r->period.' · '.$r->user->name, 'url' => route('admin.payroll.index', ['period' => $r->period])])->values()->all());
    }
}
