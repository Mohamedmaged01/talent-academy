<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.payments.index', [
            'payments' => Payment::with(['student', 'invoice', 'receiver'])
                ->when($request->user()->branchScope(), fn ($q, $b) => $q->whereHas('invoice', fn ($q) => $q->where('branch_id', $b)))
                ->when($request->query('method'), fn ($q, $v) => $q->where('method', $v))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('receipt_number', 'like', "%{$term}%")
                    ->orWhereHas('student', fn ($q) => $q->search($term))))
                ->latest('paid_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request, Invoice $invoice, PaymentService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $service->record($invoice, (float) $data['amount'], $data['method'], $data['reference'] ?? null, $data['notes'] ?? null);

        return back()->with('success', __('Payment recorded. Receipt :number', ['number' => $payment->receipt_number]));
    }

    public function receipt(Payment $payment): View
    {
        return view('admin.payments.receipt', ['payment' => $payment->load(['invoice', 'student.branch', 'receiver'])]);
    }
}
