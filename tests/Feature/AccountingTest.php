<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PayrollRecord;
use App\Services\CertificateService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\ReportService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsFixtures;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private function invoice(float $amount, float $discount = 0): Invoice
    {
        $student = $this->makeStudent();

        return Invoice::create([
            'number' => Invoice::nextNumber(), 'student_id' => $student->id, 'branch_id' => $student->branch_id,
            'description' => 'Fees', 'amount' => $amount, 'discount' => $discount, 'issued_at' => today(), 'due_date' => today()->addMonth(), 'status' => 'unpaid',
        ]);
    }

    private function balance(string $code): float
    {
        return app(LedgerService::class)->trialBalance()->first(fn ($r) => $r['account']->code === $code)['balance'];
    }

    public function test_invoices_payments_and_expenses_post_balanced_entries(): void
    {
        $this->actingAs($this->makeUser('admin'));
        $invoice = $this->invoice(12000, 1000);
        app(PaymentService::class)->record($invoice, 3000, 'cash');
        app(PaymentService::class)->record($invoice, 2000, 'vodafone_cash');
        Expense::create(['branch_id' => $this->branch()->id, 'category' => 'rent', 'description' => 'Rent', 'amount' => 500, 'spent_at' => today()]);

        $this->assertSame(4, JournalEntry::count());
        $this->assertEquals(round(JournalLine::sum('debit'), 2), round(JournalLine::sum('credit'), 2));

        $this->assertEquals(6000, $this->balance(LedgerService::RECEIVABLES));   // 11,000 billed − 5,000 received
        $this->assertEquals(2500, $this->balance(LedgerService::CASH));          // 3,000 in − 500 rent
        $this->assertEquals(2000, $this->balance(LedgerService::BANK));          // e-wallet payment
        $this->assertEquals(-11000, $this->balance(LedgerService::TUITION));     // credit balance
        $this->assertEquals(500, $this->balance('5100'));
    }

    public function test_cancelling_an_invoice_and_deleting_an_expense_post_reversals(): void
    {
        $admin = $this->makeUser('admin');
        $invoice = $this->invoice(4000);
        $this->actingAs($admin)->post(route('admin.invoices.cancel', $invoice))->assertRedirect();
        $this->assertEquals(0, $this->balance(LedgerService::RECEIVABLES));

        $expense = Expense::create(['branch_id' => $this->branch()->id, 'category' => 'supplies', 'description' => 'Paper', 'amount' => 250, 'spent_at' => today()]);
        $this->actingAs($admin)->delete(route('admin.expenses.destroy', $expense))->assertRedirect();
        $this->assertEquals(0, $this->balance('5300'));
        $this->assertEquals(0, $this->balance(LedgerService::CASH));
    }

    public function test_reports_include_records_dated_today(): void
    {
        $this->actingAs($this->makeUser('admin'));
        $this->invoice(1000);

        // Dates are stored with a time part; "as of today" must still include today's postings.
        $receivables = app(LedgerService::class)->trialBalance(today())->first(fn ($r) => $r['account']->code === LedgerService::RECEIVABLES);
        $this->assertEquals(1000, $receivables['balance']);

        $batch = $this->makeBatch();
        $student = $this->makeStudent([$batch]);
        app(CertificateService::class)->issue($student, $batch, 'شهادة', 'Certificate');
        $this->assertSame(1, ReportService::for(now()->subMonth(), now())->overview()['certificates']);
    }

    public function test_unbalanced_entries_are_rejected(): void
    {
        $this->expectException(DomainException::class);

        app(LedgerService::class)->post(today(), 'Bad', [
            ['account' => LedgerService::CASH, 'debit' => 1000],
            ['account' => '3000', 'credit' => 900],
        ]);
    }

    public function test_manual_entry_screen_validates_balance_and_can_be_reversed(): void
    {
        $admin = $this->makeUser('admin');
        $cash = Account::where('code', '1000')->value('id');
        $equity = Account::where('code', '3000')->value('id');

        $this->actingAs($admin)->post(route('admin.ledger.store'), [
            'entry_date' => today()->toDateString(), 'description' => 'Bad',
            'lines' => [['account_id' => $cash, 'debit' => 100], ['account_id' => $equity, 'credit' => 90]],
        ])->assertSessionHasErrors('lines');

        $this->actingAs($admin)->post(route('admin.ledger.store'), [
            'entry_date' => today()->toDateString(), 'description' => 'Capital',
            'lines' => [['account_id' => $cash, 'debit' => 100], ['account_id' => $equity, 'credit' => 100]],
        ])->assertRedirect(route('admin.ledger.index'));

        $entry = JournalEntry::where('kind', 'manual')->first();
        $this->actingAs($admin)->post(route('admin.ledger.reverse', $entry))->assertRedirect();
        $this->assertEquals(0, $this->balance('1000'));
        $this->actingAs($admin)->post(route('admin.ledger.reverse', $entry))->assertSessionHas('error');
    }

    public function test_rebuild_reposts_everything_and_keeps_manual_entries(): void
    {
        $this->actingAs($this->makeUser('admin'));
        $invoice = $this->invoice(3000);
        app(PaymentService::class)->record($invoice, 1000, 'cash');
        app(LedgerService::class)->post(today(), 'Opening', [['account' => '1000', 'debit' => 50], ['account' => '3000', 'credit' => 50]], null, null, 'manual');

        JournalEntry::where('kind', 'auto')->each(fn ($e) => $e->delete());
        $this->assertSame(1, JournalEntry::count());

        $result = app(LedgerService::class)->rebuild();
        $this->assertSame(2, $result['entries']);
        $this->assertSame(3, JournalEntry::count());
        $this->assertEquals(2000, $this->balance(LedgerService::RECEIVABLES));
    }

    public function test_payroll_generation_payment_and_protection(): void
    {
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('teacher', ['branch_id' => $this->branch()->id]);
        $teacher->teacherProfile()->create(['monthly_salary' => 8000]);
        $period = now()->format('Y-m');

        $this->actingAs($admin)->post(route('admin.payroll.generate'), ['period' => $period])->assertRedirect();
        $record = PayrollRecord::firstOrFail();
        $this->assertSame('draft', $record->status);

        $this->actingAs($admin)->put(route('admin.payroll.update', $record), ['basic_salary' => 8000, 'allowances' => 500, 'bonus' => 0, 'deductions' => 9000])->assertSessionHas('error');
        $this->actingAs($admin)->put(route('admin.payroll.update', $record), ['basic_salary' => 8000, 'allowances' => 500, 'bonus' => 250, 'deductions' => 750])->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.payroll.pay', $record), ['method' => 'cash'])->assertRedirect();
        $record->refresh();
        $this->assertSame('paid', $record->status);
        $this->assertEquals(8000, (float) $record->expense->amount);
        $this->assertEquals(8000, $this->balance('5000'));
        $this->assertSame(1, $teacher->unreadNotifications()->count());

        // Paid records are frozen and their expense cannot be deleted.
        $this->actingAs($admin)->delete(route('admin.payroll.destroy', $record))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.expenses.destroy', $record->expense))->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.payroll.pay', $record), ['method' => 'cash'])->assertSessionHas('error');
        $this->assertSame(1, Expense::count());

        $this->actingAs($teacher)->get(route('teacher.payslips.show', $record))->assertOk()->assertSee('8,000.00');
        $this->actingAs($this->makeUser('teacher'))->get(route('teacher.payslips.show', $record))->assertForbidden();
    }
}
