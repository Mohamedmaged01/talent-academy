<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Double-entry bookkeeping. Invoices, payments and expenses post here
 * automatically (see the model observers); accountants can add manual entries.
 * Every entry must balance: total debits equal total credits.
 */
class LedgerService
{
    public const CASH = '1000';

    public const BANK = '1010';

    public const RECEIVABLES = '1100';

    public const TUITION = '4000';

    public const EXPENSE_ACCOUNTS = [
        'salaries' => '5000', 'rent' => '5100', 'utilities' => '5200', 'supplies' => '5300',
        'marketing' => '5400', 'maintenance' => '5500', 'other' => '5900',
    ];

    /** @var array<string, int> account code => id, cached for the life of the service */
    private array $accountIds = [];

    /**
     * @param  array<int, array{account: string|int|Account, debit?: float|int|string|null, credit?: float|int|string|null, memo?: ?string}>  $lines
     */
    public function post(Carbon|string $date, string $description, array $lines, ?Model $source = null, ?int $branchId = null, string $kind = 'auto', ?int $postedBy = null): JournalEntry
    {
        $normalized = $this->normalize($lines);

        return DB::transaction(function () use ($date, $description, $normalized, $source, $branchId, $kind, $postedBy) {
            $entry = JournalEntry::create([
                // Temporary unique number; replaced below with one derived from the row id so it can never collide.
                'number' => 'TMP-'.Str::uuid(),
                'entry_date' => Carbon::parse($date)->toDateString(),
                'description' => Str::limit($description, 250, ''),
                'kind' => $kind,
                'branch_id' => $branchId,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'posted_by' => $postedBy ?? auth()->id(),
            ]);

            $entry->update(['number' => 'JE-'.Carbon::parse($date)->format('Y').'-'.str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT)]);
            $entry->lines()->createMany($normalized);

            return $entry;
        });
    }

    /** Validate lines and resolve account codes. Throws when the entry does not balance. */
    private function normalize(array $lines): array
    {
        $rows = [];
        $debits = 0;
        $credits = 0;

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
                throw new DomainException(__('Each line must have either a debit or a credit, not both.'));
            }

            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            $rows[] = [
                'account_id' => $this->accountId($line['account']),
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
            $debits += $debit;
            $credits += $credit;
        }

        if (count($rows) < 2) {
            throw new DomainException(__('A journal entry needs at least two lines.'));
        }

        if (abs(round($debits - $credits, 2)) > 0.004) {
            throw new DomainException(__('The entry does not balance: debits :debit ≠ credits :credit.', [
                'debit' => number_format($debits, 2), 'credit' => number_format($credits, 2),
            ]));
        }

        return $rows;
    }

    private function accountId(string|int|Account $account): int
    {
        if ($account instanceof Account) {
            return $account->id;
        }

        if (is_int($account)) {
            return $account;
        }

        return $this->accountIds[$account] ??= Account::where('code', $account)->value('id')
            ?? throw new DomainException("Unknown account code {$account}");
    }

    public static function cashAccountFor(string $method): string
    {
        return $method === 'cash' ? self::CASH : self::BANK;
    }

    // --- Automatic postings -------------------------------------------------

    public function invoiceIssued(Invoice $invoice): ?JournalEntry
    {
        $total = $invoice->total();

        if ($total <= 0) {
            return null;
        }

        return $this->post($invoice->issued_at, "Invoice {$invoice->number}", [
            ['account' => self::RECEIVABLES, 'debit' => $total],
            ['account' => self::TUITION, 'credit' => $total],
        ], $invoice, $invoice->branch_id);
    }

    /** Cancelling an invoice reverses whatever had not been paid. */
    public function invoiceCancelled(Invoice $invoice, ?Carbon $date = null): ?JournalEntry
    {
        $unpaid = round($invoice->total() - (float) $invoice->paid_amount, 2);

        if ($unpaid <= 0) {
            return null;
        }

        return $this->post($date ?? now(), "Cancellation of invoice {$invoice->number}", [
            ['account' => self::TUITION, 'debit' => $unpaid],
            ['account' => self::RECEIVABLES, 'credit' => $unpaid],
        ], $invoice, $invoice->branch_id, 'reversal');
    }

    public function paymentReceived(Payment $payment): JournalEntry
    {
        return $this->post($payment->paid_at, "Receipt {$payment->receipt_number}", [
            ['account' => self::cashAccountFor($payment->method), 'debit' => $payment->amount],
            ['account' => self::RECEIVABLES, 'credit' => $payment->amount],
        ], $payment, $payment->invoice?->branch_id);
    }

    public function expenseRecorded(Expense $expense): JournalEntry
    {
        return $this->post($expense->spent_at, "Expense: {$expense->description}", [
            ['account' => self::EXPENSE_ACCOUNTS[$expense->category] ?? self::EXPENSE_ACCOUNTS['other'], 'debit' => $expense->amount],
            ['account' => self::CASH, 'credit' => $expense->amount],
        ], $expense, $expense->branch_id);
    }

    public function expenseRemoved(Expense $expense): JournalEntry
    {
        return $this->post(now(), "Reversal of deleted expense #{$expense->id}: {$expense->description}", [
            ['account' => self::CASH, 'debit' => $expense->amount],
            ['account' => self::EXPENSE_ACCOUNTS[$expense->category] ?? self::EXPENSE_ACCOUNTS['other'], 'credit' => $expense->amount],
        ], null, $expense->branch_id, 'reversal');
    }

    /**
     * Throw away every automatic posting and post again from the current invoices,
     * payments and expenses. Manual entries are kept. Used once after upgrading
     * an existing installation, and whenever the integrity check reports gaps.
     *
     * @return array{entries: int}
     */
    public function rebuild(): array
    {
        return DB::transaction(function () {
            $ids = JournalEntry::whereIn('kind', ['auto', 'reversal'])->pluck('id');
            JournalLine::whereIn('journal_entry_id', $ids)->delete();
            JournalEntry::whereIn('id', $ids)->delete();

            $count = 0;

            Invoice::orderBy('id')->each(function (Invoice $invoice) use (&$count) {
                $count += $this->invoiceIssued($invoice) ? 1 : 0;

                if ($invoice->status === 'cancelled') {
                    $count += $this->invoiceCancelled($invoice, $invoice->updated_at) ? 1 : 0;
                }
            });

            Payment::with('invoice')->orderBy('id')->each(function (Payment $payment) use (&$count) {
                $this->paymentReceived($payment);
                $count++;
            });

            Expense::orderBy('id')->each(function (Expense $expense) use (&$count) {
                $this->expenseRecorded($expense);
                $count++;
            });

            return ['entries' => $count];
        });
    }

    // --- Reports --------------------------------------------------------------

    /**
     * Debit/credit totals per account up to a date.
     *
     * @return Collection<int, array{account: Account, debit: float, credit: float, balance: float}>
     */
    public function trialBalance(?Carbon $asOf = null, ?Carbon $from = null): Collection
    {
        $totals = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            // Dates are stored as "Y-m-d 00:00:00": include the whole as-of day.
            ->when($asOf, fn ($q) => $q->where('journal_entries.entry_date', '<=', $asOf->toDateString().' 23:59:59'))
            ->when($from, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from->toDateString()))
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id, sum(journal_lines.debit) as debit, sum(journal_lines.credit) as credit')
            ->get()
            ->keyBy('account_id');

        return Account::orderBy('code')->get()->map(function (Account $account) use ($totals) {
            $debit = round((float) ($totals[$account->id]->debit ?? 0), 2);
            $credit = round((float) ($totals[$account->id]->credit ?? 0), 2);

            return ['account' => $account, 'debit' => $debit, 'credit' => $credit, 'balance' => round($debit - $credit, 2)];
        });
    }

    /** Revenue minus expenses from the ledger for a period. */
    public function incomeStatement(Carbon $from, Carbon $to): array
    {
        $rows = $this->trialBalance($to, $from);
        $revenue = $rows->where('account.type', 'revenue')->map(fn ($r) => $r + ['amount' => round($r['credit'] - $r['debit'], 2)])->values();
        $expenses = $rows->where('account.type', 'expense')->map(fn ($r) => $r + ['amount' => round($r['debit'] - $r['credit'], 2)])->values();

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => round($revenue->sum('amount'), 2),
            'total_expenses' => round($expenses->sum('amount'), 2),
            'net' => round($revenue->sum('amount') - $expenses->sum('amount'), 2),
        ];
    }
}
