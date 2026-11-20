<?php

namespace App\Services;

use App\Models\AdmissionRequest;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\ExamResult;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for the administrator's reports and analytics pages. Every method
 * works on a date range and, optionally, one branch.
 */
class ReportService
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private ?int $branchId = null,
    ) {}

    public static function for(Carbon $from, Carbon $to, ?int $branchId = null): self
    {
        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay(), $branchId);
    }

    private function batchQuery()
    {
        return Batch::query()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
    }

    /**
     * Bounds for date columns. Eloquent stores dates as "Y-m-d H:i:s", and SQLite
     * compares them as text, so the upper bound must include the whole last day.
     */
    private function range(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString().' 23:59:59'];
    }

    public function overview(): array
    {
        $batchIds = $this->batchQuery()->pluck('id');
        $attendance = AttendanceRecord::whereIn('batch_id', $batchIds)->whereBetween('date', $this->range());
        $attTotal = (clone $attendance)->count();
        $examPercents = $this->examPercents($batchIds);
        $admissions = AdmissionRequest::whereBetween('created_at', [$this->from, $this->to])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
        $admissionsTotal = (clone $admissions)->count();
        $finance = $this->financeTotals();

        return [
            'active_students' => Student::where('status', 'active')->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->count(),
            'new_enrollments' => Enrollment::whereIn('batch_id', $batchIds)->whereBetween('enrolled_at', $this->range())->count(),
            'active_batches' => $this->batchQuery()->where('status', 'active')->count(),
            'attendance_rate' => $attTotal ? round((clone $attendance)->whereIn('status', ['present', 'late'])->count() / $attTotal * 100, 1) : null,
            'exam_average' => $examPercents->isNotEmpty() ? round($examPercents->avg('percent'), 1) : null,
            'pass_rate' => $examPercents->isNotEmpty() ? round($examPercents->where('passed', true)->count() / $examPercents->count() * 100, 1) : null,
            'certificates' => Certificate::whereIn('batch_id', $batchIds)->whereBetween('issued_at', $this->range())->where('status', 'valid')->count(),
            'admissions' => $admissionsTotal,
            'admissions_accepted' => (clone $admissions)->where('status', 'accepted')->count(),
            'conversion' => $admissionsTotal ? round((clone $admissions)->where('status', 'accepted')->count() / $admissionsTotal * 100, 1) : null,
        ] + $finance;
    }

    public function financeTotals(): array
    {
        $revenue = (float) Payment::whereBetween('paid_at', [$this->from, $this->to])
            ->when($this->branchId, fn ($q) => $q->whereHas('invoice', fn ($q) => $q->where('branch_id', $this->branchId)))
            ->sum('amount');
        $expenses = (float) Expense::whereBetween('spent_at', $this->range())
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->sum('amount');
        $open = Invoice::open()->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->get();

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net' => round($revenue - $expenses, 2),
            'outstanding' => round($open->sum(fn (Invoice $i) => $i->balance()), 2),
        ];
    }

    /** Published exam results in range, as percentages. */
    private function examPercents(Collection $batchIds): Collection
    {
        return ExamResult::with('exam')
            ->whereHas('exam', fn ($q) => $q->whereIn('batch_id', $batchIds)->where('results_published', true)->whereBetween('exam_date', $this->range()))
            ->get()
            ->map(fn (ExamResult $r) => [
                'batch_id' => $r->exam->batch_id,
                'percent' => $r->exam->max_score > 0 ? $r->score / $r->exam->max_score * 100 : 0,
                'passed' => (float) $r->score >= $r->exam->pass_score,
            ]);
    }

    /**
     * One row per class of a pathway with attendance, exams, homework and certificates in range.
     *
     * @return Collection<int, array>
     */
    public function pathwayBatches(string $pathway): Collection
    {
        $batches = $this->batchQuery()->with(['course', 'teacher'])
            ->whereHas('course', fn ($q) => $q->where('pathway', $pathway))
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $this->range()[1]))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $this->from->toDateString()))
            ->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $ids = $batches->pluck('id');
        $attendance = AttendanceRecord::whereIn('batch_id', $ids)->whereBetween('date', $this->range())
            ->selectRaw("batch_id, count(*) as total, sum(case when status in ('present', 'late') then 1 else 0 end) as attended")
            ->groupBy('batch_id')->get()->keyBy('batch_id');
        $exams = $this->examPercents($ids)->groupBy('batch_id');
        $certificates = Certificate::whereIn('batch_id', $ids)->where('status', 'valid')->whereBetween('issued_at', $this->range())
            ->selectRaw('batch_id, count(*) as total')->groupBy('batch_id')->pluck('total', 'batch_id');

        return $batches->map(function (Batch $batch) use ($attendance, $exams, $certificates) {
            $att = $attendance->get($batch->id);
            $results = $exams->get($batch->id, collect());
            $assignments = $batch->assignments()->whereBetween('due_at', [$this->from, $this->to])->withCount('submissions')->get();
            $expected = $assignments->count() * $batch->students_count;

            return [
                'batch' => $batch,
                'students' => $batch->students_count,
                'attendance' => $att && $att->total ? round($att->attended / $att->total * 100, 1) : null,
                'average' => $results->isNotEmpty() ? round($results->avg('percent'), 1) : null,
                'pass_rate' => $results->isNotEmpty() ? round($results->where('passed', true)->count() / $results->count() * 100, 1) : null,
                'submission_rate' => $expected > 0 ? round(min(100, $assignments->sum('submissions_count') / $expected * 100), 1) : null,
                'certificates' => (int) ($certificates[$batch->id] ?? 0),
            ];
        });
    }

    public function financial(): array
    {
        $months = collect();
        $cursor = $this->from->copy()->startOfMonth();

        while ($cursor->lte($this->to) && $months->count() < 24) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        $payments = Payment::whereBetween('paid_at', [$this->from, $this->to])
            ->when($this->branchId, fn ($q) => $q->whereHas('invoice', fn ($q) => $q->where('branch_id', $this->branchId)))
            ->get(['amount', 'paid_at', 'method']);
        $expenses = Expense::whereBetween('spent_at', $this->range())
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->get(['amount', 'spent_at', 'category']);

        $monthly = $months->map(fn (Carbon $m) => [
            'label' => $m->translatedFormat('M Y'),
            'revenue' => round($payments->filter(fn ($p) => $p->paid_at->isSameMonth($m))->sum('amount'), 2),
            'expenses' => round($expenses->filter(fn ($e) => $e->spent_at->isSameMonth($m))->sum('amount'), 2),
        ]);

        // Receivables aging: open invoices bucketed by days past due today.
        $aging = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $open = Invoice::open()->with('student')->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->get();

        foreach ($open as $invoice) {
            $days = $invoice->due_date->isPast() ? (int) $invoice->due_date->diffInDays(today()) : 0;
            $bucket = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => '1-30',
                $days <= 60 => '31-60',
                $days <= 90 => '61-90',
                default => '90+',
            };
            $aging[$bucket] += $invoice->balance();
        }

        $debtors = $open->groupBy('student_id')->map(fn ($invoices) => [
            'student' => $invoices->first()->student,
            'balance' => round($invoices->sum(fn ($i) => $i->balance()), 2),
            'overdue' => round($invoices->filter(fn ($i) => $i->due_date->isPast())->sum(fn ($i) => $i->balance()), 2),
        ])->sortByDesc('balance')->take(10)->values();

        return [
            'monthly' => $monthly,
            'by_method' => $payments->groupBy('method')->map(fn ($g) => round($g->sum('amount'), 2))->sortDesc(),
            'by_category' => $expenses->groupBy('category')->map(fn ($g) => round($g->sum('amount'), 2))->sortDesc(),
            'aging' => array_map(fn ($v) => round($v, 2), $aging),
            'debtors' => $debtors,
            'income' => app(LedgerService::class)->incomeStatement($this->from, $this->to),
        ] + $this->financeTotals();
    }

    public function branches(): Collection
    {
        return Branch::orderBy('code')->get()->map(function (Branch $branch) {
            $scoped = self::for($this->from, $this->to, $branch->id);

            return [
                'branch' => $branch,
                'students' => $branch->students()->where('status', 'active')->count(),
                'batches' => $branch->batches()->where('status', 'active')->count(),
            ] + $scoped->financeTotals();
        });
    }

    public function faculty(): Collection
    {
        return User::where('role', 'teacher')->with('teacherProfile')->orderBy('name')->get()->map(function (User $teacher) {
            $batches = $this->batchQuery()->where('teacher_id', $teacher->id)->whereIn('status', ['active', 'completed'])->get();
            $ids = $batches->pluck('id');
            $att = AttendanceRecord::whereIn('batch_id', $ids)->whereBetween('date', $this->range());
            $total = (clone $att)->count();
            $exams = $this->examPercents($ids);

            return [
                'teacher' => $teacher,
                'batches' => $batches->where('status', 'active')->count(),
                'students' => Enrollment::whereIn('batch_id', $ids)->where('status', 'active')->distinct()->count('student_id'),
                'attendance' => $total ? round((clone $att)->whereIn('status', ['present', 'late'])->count() / $total * 100, 1) : null,
                'average' => $exams->isNotEmpty() ? round($exams->avg('percent'), 1) : null,
                'to_grade' => AssignmentSubmission::whereNull('graded_at')
                    ->whereHas('assignment', fn ($q) => $q->whereIn('batch_id', $ids))->count(),
            ];
        })->filter(fn ($row) => $row['batches'] > 0 || $row['students'] > 0)->values();
    }
}
