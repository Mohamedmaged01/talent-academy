<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Services\LedgerService;
use App\Support\Csv;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** General journal, chart of accounts, account ledgers and the trial balance. */
class LedgerController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth()->subMonths(2);
        $to = $request->date('to') ?? now();

        return view('admin.ledger.index', [
            'entries' => JournalEntry::with(['lines.account', 'poster'])
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
                ->when($request->query('kind'), fn ($q, $v) => $q->where('kind', $v))
                ->when($request->query('account'), fn ($q, $v) => $q->whereHas('lines', fn ($q) => $q->where('account_id', $v)))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('description', 'like', "%{$v}%")->orWhere('number', 'like', "%{$v}%")))
                ->orderByDesc('entry_date')->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'accounts' => Account::orderBy('code')->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(): View
    {
        return view('admin.ledger.create', [
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
            'branches' => Branch::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:250'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'lines' => ['required', 'array', 'min:2', 'max:20'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('is_active', true)],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'lines.*.memo' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $entry = $this->ledger->post($data['entry_date'], $data['description'], array_map(fn ($l) => [
                'account' => (int) $l['account_id'], 'debit' => $l['debit'] ?? 0, 'credit' => $l['credit'] ?? 0, 'memo' => $l['memo'] ?? null,
            ], $data['lines']), null, $data['branch_id'] ?? null, 'manual');
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['lines' => $e->getMessage()]);
        }

        ActivityLog::record('ledger.manual', "Manual journal entry {$entry->number} posted", $entry);

        return redirect()->route('admin.ledger.index')->with('success', __('Journal entry :number posted.', ['number' => $entry->number]));
    }

    /** Undo a manual entry by posting its mirror image; entries are never edited or deleted. */
    public function reverse(JournalEntry $entry): RedirectResponse
    {
        abort_unless($entry->kind === 'manual', 403, __('Automatic entries are corrected by changing the invoice, payment or expense.'));

        if (JournalEntry::where('source_type', $entry->getMorphClass())->where('source_id', $entry->id)->exists()) {
            return back()->with('error', __('This entry has already been reversed.'));
        }

        $reversal = $this->ledger->post(now(), "Reversal of {$entry->number}: {$entry->description}",
            $entry->lines->map(fn (JournalLine $l) => ['account' => $l->account_id, 'debit' => $l->credit, 'credit' => $l->debit, 'memo' => $l->memo])->all(),
            $entry, $entry->branch_id, 'reversal');

        ActivityLog::record('ledger.reversed', "Journal entry {$entry->number} reversed by {$reversal->number}", $reversal);

        return back()->with('success', __('Entry reversed by :number.', ['number' => $reversal->number]));
    }

    public function trialBalance(Request $request): View|StreamedResponse
    {
        $asOf = $request->date('as_of') ?? now();
        $rows = $this->ledger->trialBalance($asOf)->filter(fn ($r) => $r['debit'] > 0 || $r['credit'] > 0)->values();

        if ($request->query('export') === 'csv') {
            return Csv::download('trial-balance-'.$asOf->format('Y-m-d').'.csv', ['Code', 'Account', 'Type', 'Debit', 'Credit', 'Debit balance', 'Credit balance'],
                $rows->map(fn ($r) => [$r['account']->code, $r['account']->name_en, $r['account']->type, $r['debit'], $r['credit'],
                    $r['balance'] > 0 ? $r['balance'] : '', $r['balance'] < 0 ? -$r['balance'] : '']));
        }

        $start = $asOf->copy()->startOfYear();

        return view('admin.ledger.trial-balance', [
            'rows' => $rows,
            'asOf' => $asOf,
            'totals' => ['debit' => round($rows->sum('debit'), 2), 'credit' => round($rows->sum('credit'), 2)],
            'income' => $this->ledger->incomeStatement($start, $asOf),
            'start' => $start,
            'missing' => [
                'invoices' => Invoice::whereDoesntHave('journalEntries')->where('status', '!=', 'cancelled')->count(),
                'payments' => Payment::whereDoesntHave('journalEntries')->count(),
                'expenses' => Expense::whereDoesntHave('journalEntries')->count(),
            ],
        ]);
    }

    public function accounts(): View
    {
        $totals = $this->ledger->trialBalance()->keyBy(fn ($r) => $r['account']->id);

        return view('admin.ledger.accounts', [
            'accounts' => Account::orderBy('code')->get(),
            'totals' => $totals,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z.-]+$/', 'unique:accounts,code'],
            'name_ar' => ['required', 'string', 'max:150'],
            'name_en' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Account::TYPES)],
        ]);

        $account = Account::create($data + ['is_system' => false, 'is_active' => true]);
        ActivityLog::record('ledger.account', "Account {$account->code} {$account->name_en} added", $account);

        return back()->with('success', __('Account added.'));
    }

    public function toggleAccount(Account $account): RedirectResponse
    {
        if ($account->is_system) {
            return back()->with('error', __('System accounts are used by automatic postings and cannot be deactivated.'));
        }

        $account->update(['is_active' => ! $account->is_active]);

        return back()->with('success', $account->is_active ? __('Account activated.') : __('Account deactivated.'));
    }

    public function account(Request $request, Account $account): View
    {
        $from = $request->date('from') ?? now()->startOfYear();
        $to = $request->date('to') ?? now();

        $opening = (float) JournalLine::where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->where('entry_date', '<', $from->toDateString()))
            ->selectRaw('coalesce(sum(debit), 0) - coalesce(sum(credit), 0) as net')->value('net');

        $lines = JournalLine::with('entry')
            ->where('account_id', $account->id)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->orderBy('journal_entries.entry_date')->orderBy('journal_entries.id')
            ->select('journal_lines.*')
            ->get();

        $running = $opening;
        $rows = $lines->map(function (JournalLine $line) use (&$running) {
            $running = round($running + (float) $line->debit - (float) $line->credit, 2);

            return ['line' => $line, 'balance' => $running];
        });

        return view('admin.ledger.account', compact('account', 'rows', 'opening', 'from', 'to'));
    }

    public function rebuild(): RedirectResponse
    {
        $result = $this->ledger->rebuild();
        ActivityLog::record('ledger.rebuilt', "Automatic ledger postings rebuilt ({$result['entries']} entries)");

        return back()->with('success', __('Ledger rebuilt from invoices, payments and expenses: :count entries.', ['count' => $result['entries']]));
    }
}
