<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        // Refresh overdue flags so the list reflects today's date.
        Invoice::whereIn('status', ['unpaid'])->where('due_date', '<', now()->toDateString())->update(['status' => 'overdue']);

        $invoices = Invoice::with(['student', 'branch'])
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('number', 'like', "%{$term}%")
                ->orWhereHas('student', fn ($q) => $q->search($term))))
            ->latest('issued_at')->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        return view('admin.invoices.create', [
            'students' => Student::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'code', 'branch_id']),
            'batches' => Batch::with('course')->whereIn('status', ['upcoming', 'active'])->get(),
            'selectedStudent' => $request->query('student'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
            'issued_at' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issued_at'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $student = Student::findOrFail($data['student_id']);
        $installments = (int) ($data['installments'] ?? 1);
        $net = round($data['amount'] - ($data['discount'] ?? 0), 2);

        $invoices = DB::transaction(function () use ($data, $student, $installments, $net) {
            $created = collect();
            $share = floor($net / $installments * 100) / 100;

            for ($i = 0; $i < $installments; $i++) {
                // The last installment absorbs any rounding remainder so totals always match.
                $amount = $i === $installments - 1 ? round($net - $share * ($installments - 1), 2) : $share;

                $created->push(Invoice::create([
                    'number' => Invoice::nextNumber(),
                    'student_id' => $student->id,
                    'branch_id' => $student->branch_id,
                    'batch_id' => $data['batch_id'] ?? null,
                    'description' => $installments > 1 ? $data['description'].' — '.__('Installment :n of :total', ['n' => $i + 1, 'total' => $installments]) : $data['description'],
                    'amount' => $installments > 1 ? $amount : $data['amount'],
                    'discount' => $installments > 1 ? 0 : ($data['discount'] ?? 0),
                    'issued_at' => $data['issued_at'],
                    'due_date' => \Illuminate\Support\Carbon::parse($data['due_date'])->addMonths($i),
                    'status' => 'unpaid',
                    'notes' => $data['notes'] ?? null,
                ]));
            }

            return $created;
        });

        ActivityLog::record('invoice.created', "{$invoices->count()} invoice(s) issued to {$student->full_name}", $invoices->first());

        return redirect()->route('admin.invoices.show', $invoices->first())->with('success', __('Invoice issued.'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['student', 'branch', 'batch.course', 'payments.receiver']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'methods' => Payment::METHODS,
        ]);
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', __('An invoice with recorded payments cannot be cancelled.'));
        }

        $invoice->update(['status' => 'cancelled']);
        ActivityLog::record('invoice.cancelled', "Invoice {$invoice->number} cancelled", $invoice);

        return back()->with('success', __('Invoice cancelled.'));
    }
}
