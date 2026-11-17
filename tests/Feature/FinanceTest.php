<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@test.local', 'role' => 'admin', 'password' => 'password']);
        $branch = Branch::create(['code' => 'B1', 'name_ar' => 'فرع', 'name_en' => 'Branch']);
        $this->student = Student::create(['branch_id' => $branch->id, 'code' => 'TA-1', 'full_name' => 'طالب']);
    }

    private function invoice(float $amount = 12000, float $discount = 0): Invoice
    {
        return Invoice::create([
            'number' => Invoice::nextNumber(), 'student_id' => $this->student->id, 'branch_id' => $this->student->branch_id,
            'description' => 'Tuition', 'amount' => $amount, 'discount' => $discount,
            'issued_at' => now(), 'due_date' => now()->addWeek(), 'status' => 'unpaid',
        ]);
    }

    public function test_partial_then_full_payment_updates_balance_and_status(): void
    {
        $invoice = $this->invoice(12000);

        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 3000, 'method' => 'cash'])->assertSessionHas('success');
        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame(9000.0, $invoice->balance());

        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 9000, 'method' => 'instapay', 'reference' => 'IP-1']);
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0.0, $invoice->balance());
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_over_collection_is_rejected(): void
    {
        $invoice = $this->invoice(12000);
        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 3000, 'method' => 'cash']);

        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 10000, 'method' => 'cash'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(9000.0, $invoice->fresh()->balance());
    }

    public function test_discount_reduces_amount_due(): void
    {
        $invoice = $this->invoice(5000, 500);

        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 4600, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => 4500, 'method' => 'cash'])->assertSessionHas('success');
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_cancelled_invoices_cannot_take_payments_and_paid_ones_cannot_be_cancelled(): void
    {
        $open = $this->invoice(1000);
        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $open))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.payments.store', $open), ['amount' => 100, 'method' => 'cash'])->assertSessionHasErrors('amount');

        $paid = $this->invoice(1000);
        $this->actingAs($this->admin)->post(route('admin.payments.store', $paid), ['amount' => 1000, 'method' => 'cash']);
        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $paid))->assertSessionHas('error');
        $this->assertSame('paid', $paid->fresh()->status);
    }

    public function test_installment_plan_splits_the_net_total_exactly(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'student_id' => $this->student->id, 'description' => 'AI Diploma', 'amount' => 10000, 'discount' => 0,
            'issued_at' => now()->toDateString(), 'due_date' => now()->addDays(10)->toDateString(), 'installments' => 3,
        ])->assertRedirect();

        $invoices = Invoice::orderBy('due_date')->get();
        $this->assertCount(3, $invoices);
        $this->assertSame(10000.0, round($invoices->sum(fn ($i) => $i->total()), 2));
        $this->assertTrue($invoices[1]->due_date->isSameDay($invoices[0]->due_date->copy()->addMonth()));
    }

    public function test_receipt_numbers_are_sequential_and_unique(): void
    {
        $invoice = $this->invoice(3000);
        foreach ([1000, 1000, 1000] as $amount) {
            $this->actingAs($this->admin)->post(route('admin.payments.store', $invoice), ['amount' => $amount, 'method' => 'cash']);
        }

        $numbers = $invoice->payments()->pluck('receipt_number')->sort()->values();
        $year = now()->format('Y');
        $this->assertSame(["RCP-{$year}-00001", "RCP-{$year}-00002", "RCP-{$year}-00003"], $numbers->all());
    }

    public function test_student_with_invoices_is_withdrawn_not_deleted(): void
    {
        $this->invoice(1000);

        $this->actingAs($this->admin)->delete(route('admin.students.destroy', $this->student))->assertSessionHas('warning');
        $this->assertSame('withdrawn', $this->student->fresh()->status);
    }

    public function test_finance_overview_includes_expenses_on_the_last_day_of_the_range(): void
    {
        \App\Models\Expense::create([
            'branch_id' => $this->student->branch_id, 'category' => 'rent', 'description' => 'Rent',
            'amount' => 1234, 'spent_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.finance', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertViewHas('summary', fn ($summary) => (float) $summary['expenses'] === 1234.0);
    }

    public function test_csv_export_neutralises_formula_injection(): void
    {
        $this->student->update(['full_name' => '=HYPERLINK("http://evil")']);

        $csv = $this->actingAs($this->admin)->get(route('admin.students.index', ['export' => 'csv']))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
