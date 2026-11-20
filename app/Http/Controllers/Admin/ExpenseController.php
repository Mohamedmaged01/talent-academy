<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\PayrollRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.expenses.index', [
            'expenses' => Expense::with(['branch', 'recorder'])->forAdminBranch()
                ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
                ->when($request->query('branch'), fn ($q, $v) => $q->where('branch_id', $v))
                ->latest('spent_at')->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'branches' => Branch::where('status', '!=', 'archived')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'spent_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $expense = Expense::create($data + ['recorded_by' => auth()->id()]);
        ActivityLog::record('expense.created', "Expense of {$expense->amount} EGP ({$expense->category}) recorded", $expense);

        return back()->with('success', __('Expense recorded.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        if (PayrollRecord::where('expense_id', $expense->id)->exists()) {
            return back()->with('error', __('This expense was created by a payroll payment and cannot be deleted.'));
        }

        ActivityLog::record('expense.deleted', "Expense #{$expense->id} of {$expense->amount} EGP deleted");
        $expense->delete();

        return back()->with('success', __('Expense deleted.'));
    }
}
