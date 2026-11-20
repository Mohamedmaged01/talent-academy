<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Support\Csv;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Monthly payroll for teaching staff. Records start as drafts built from each
 * teacher's monthly salary; paying one records a "salaries" expense, which the
 * ledger posts automatically (salaries expense / cash).
 */
class PayrollController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $period = $this->period($request->query('period'));
        $records = PayrollRecord::with(['user.teacherProfile', 'branch', 'payer'])->forAdminBranch()->where('period', $period)
            ->join('users', 'users.id', '=', 'payroll_records.user_id')
            ->orderBy('users.name')->select('payroll_records.*')
            ->get();

        if ($request->query('export') === 'csv') {
            return Csv::download("payroll-{$period}.csv", ['Staff', 'Email', 'Basic', 'Allowances', 'Bonus', 'Deductions', 'Net', 'Status', 'Paid at'],
                $records->map(fn (PayrollRecord $r) => [$r->user->name, $r->user->email, (float) $r->basic_salary, (float) $r->allowances, (float) $r->bonus,
                    (float) $r->deductions, $r->net(), $r->status, $r->paid_at?->toDateTimeString()]));
        }

        return view('admin.payroll.index', [
            'period' => $period,
            'records' => $records,
            'periods' => PayrollRecord::select('period')->distinct()->orderByDesc('period')->pluck('period'),
            'missing' => $this->eligibleStaff()->whereNotIn('id', $records->pluck('user_id'))->count(),
            'totals' => [
                'net' => $records->sum(fn ($r) => $r->net()),
                'paid' => $records->where('status', 'paid')->sum(fn ($r) => $r->net()),
                'draft' => $records->where('status', 'draft')->sum(fn ($r) => $r->net()),
            ],
        ]);
    }

    /** Create draft records for every teacher with a salary who has none for the period yet. */
    public function generate(Request $request): RedirectResponse
    {
        $period = $this->period($request->input('period'));
        $existing = PayrollRecord::where('period', $period)->pluck('user_id');
        $count = 0;

        foreach ($this->eligibleStaff()->whereNotIn('id', $existing)->get() as $staff) {
            PayrollRecord::create([
                'user_id' => $staff->id,
                'branch_id' => $staff->branch_id,
                'period' => $period,
                'basic_salary' => $staff->teacherProfile->monthly_salary,
                'status' => 'draft',
            ]);
            $count++;
        }

        ActivityLog::record('payroll.generated', "Payroll drafts for {$period}: {$count} created");

        return redirect()->route('admin.payroll.index', ['period' => $period])
            ->with('success', trans_choice(':count payroll record created.|:count payroll records created.', $count, ['count' => $count]));
    }

    public function update(Request $request, PayrollRecord $record): RedirectResponse
    {
        if ($record->isPaid()) {
            return back()->with('error', __('A paid payroll record cannot be changed.'));
        }

        $data = $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'allowances' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'bonus' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'deductions' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:190'],
        ]);

        $record->fill($data);

        if ($record->net() < 0) {
            return back()->with('error', __('Deductions cannot exceed the gross pay.'));
        }

        $record->save();

        return back()->with('success', __('Payroll record updated.'));
    }

    public function pay(Request $request, PayrollRecord $record): RedirectResponse
    {
        $data = $request->validate(['method' => ['required', Rule::in(Payment::METHODS)]]);

        if ($record->isPaid()) {
            return back()->with('error', __('This payroll record is already paid.'));
        }

        $this->settle($record, $data['method']);

        return back()->with('success', __('Salary paid and recorded as an expense.'));
    }

    public function payAll(Request $request): RedirectResponse
    {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m'], 'method' => ['required', Rule::in(Payment::METHODS)]]);
        $records = PayrollRecord::where('period', $data['period'])->where('status', 'draft')->get();

        foreach ($records as $record) {
            $this->settle($record, $data['method']);
        }

        return back()->with('success', trans_choice(':count salary paid.|:count salaries paid.', $records->count(), ['count' => $records->count()]));
    }

    public function destroy(PayrollRecord $record): RedirectResponse
    {
        if ($record->isPaid()) {
            return back()->with('error', __('A paid payroll record cannot be deleted.'));
        }

        $record->delete();

        return back()->with('success', __('Payroll record deleted.'));
    }

    public function payslip(PayrollRecord $record): View
    {
        return view('payroll.payslip', ['record' => $record->load(['user.teacherProfile', 'branch', 'payer'])]);
    }

    private function settle(PayrollRecord $record, string $method): void
    {
        DB::transaction(function () use ($record, $method) {
            $locked = PayrollRecord::whereKey($record->id)->lockForUpdate()->first();

            if ($locked->isPaid() || $locked->net() <= 0) {
                return;
            }

            $branchId = $locked->branch_id ?? Branch::active()->value('id') ?? Branch::value('id');
            $expense = Expense::create([
                'branch_id' => $branchId,
                'category' => 'salaries',
                'description' => "Payroll {$locked->period} — {$locked->user->name}",
                'amount' => $locked->net(),
                'spent_at' => now()->toDateString(),
                'recorded_by' => auth()->id(),
            ]);

            $locked->update(['status' => 'paid', 'method' => $method, 'paid_at' => now(), 'paid_by' => auth()->id(), 'expense_id' => $expense->id, 'branch_id' => $branchId]);
            ActivityLog::record('payroll.paid', "Salary {$locked->period} paid to {$locked->user->name}: {$locked->net()} EGP", $locked);

            Notify::users($locked->user, 'Your salary for :period has been paid', ['period' => $locked->period], route('teacher.payslips'), 'banknote');
        });
    }

    private function eligibleStaff()
    {
        return User::where('role', 'teacher')->where('is_active', true)
            ->whereHas('teacherProfile', fn ($q) => $q->where('monthly_salary', '>', 0))
            ->with('teacherProfile');
    }

    private function period(?string $value): string
    {
        return $value && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) ? $value : now()->format('Y-m');
    }
}
