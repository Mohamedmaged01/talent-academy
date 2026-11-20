<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __invoke(Request $request): View
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();
        $branchId = $request->user()->branchScope() ?? $request->query('branch');

        $payments = Payment::with('invoice')
            ->whereBetween('paid_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId)))
            ->get();

        $expenses = Expense::whereBetween('spent_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $openInvoices = Invoice::open()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();

        $revenue = (float) $payments->sum('amount');
        $spent = (float) $expenses->sum('amount');

        return view('admin.finance.index', [
            'from' => $from,
            'to' => $to,
            'branches' => Branch::orderBy('id')->get(),
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $spent,
                'net' => $revenue - $spent,
                'outstanding' => $openInvoices->sum(fn (Invoice $i) => $i->balance()),
                'overdue' => $openInvoices->filter(fn (Invoice $i) => $i->due_date->isPast())->sum(fn (Invoice $i) => $i->balance()),
                'invoices_open' => $openInvoices->count(),
            ],
            'byMethod' => $payments->groupBy('method')->map->sum('amount')->sortDesc(),
            'byCategory' => $expenses->groupBy('category')->map->sum('amount')->sortDesc(),
            'byBranch' => Branch::orderBy('id')->get()->map(fn (Branch $b) => [
                'branch' => $b,
                'revenue' => (float) $payments->filter(fn ($p) => $p->invoice?->branch_id === $b->id)->sum('amount'),
                'expenses' => (float) $expenses->where('branch_id', $b->id)->sum('amount'),
            ]),
            'recentPayments' => $payments->sortByDesc('paid_at')->take(8)->load('student'),
        ]);
    }
}
