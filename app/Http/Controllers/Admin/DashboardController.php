<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdmissionRequest;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\BatchSchedule;
use App\Models\Expense;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();

        $stats = [
            'students' => Student::where('status', 'active')->count(),
            'students_new' => Student::where('created_at', '>=', $monthStart)->count(),
            'teachers' => User::where('role', 'teacher')->where('is_active', true)->count(),
            'batches' => Batch::where('status', 'active')->count(),
            'admissions_pending' => AdmissionRequest::where('status', 'pending')->count(),
            'collected_month' => (float) Payment::where('paid_at', '>=', $monthStart)->sum('amount'),
            'outstanding' => Invoice::open()->get()->sum(fn (Invoice $i) => $i->balance()),
            'inquiries_new' => Inquiry::where('status', 'new')->count(),
        ];

        // Revenue vs expenses for the last 6 months (grouped in PHP so it works on SQLite and MySQL alike).
        $from = now()->subMonths(5)->startOfMonth();
        $months = collect(range(0, 5))->map(fn ($i) => $from->copy()->addMonths($i));
        $payments = Payment::where('paid_at', '>=', $from)->get(['amount', 'paid_at'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'));
        $expenses = Expense::where('spent_at', '>=', $from)->get(['amount', 'spent_at'])
            ->groupBy(fn ($e) => $e->spent_at->format('Y-m'));

        $financeChart = [
            'labels' => $months->map(fn (Carbon $m) => $m->translatedFormat('M Y'))->all(),
            'revenue' => $months->map(fn (Carbon $m) => round((float) ($payments[$m->format('Y-m')] ?? collect())->sum('amount')))->all(),
            'expenses' => $months->map(fn (Carbon $m) => round((float) ($expenses[$m->format('Y-m')] ?? collect())->sum('amount')))->all(),
        ];

        $attendance = AttendanceRecord::where('date', '>=', now()->subDays(30))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $attendanceTotal = max(1, $attendance->sum());

        $pathways = Student::where('status', 'active')->selectRaw('pathway, count(*) as total')->groupBy('pathway')->pluck('total', 'pathway');

        $todaySchedule = BatchSchedule::with(['batch.course', 'batch.teacher'])
            ->where('day_of_week', now()->dayOfWeek)
            ->whereHas('batch', fn ($q) => $q->where('status', 'active'))
            ->orderBy('starts_at')
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'financeChart' => $financeChart,
            'attendance' => $attendance,
            'attendanceRate' => round((($attendance['present'] ?? 0) + ($attendance['late'] ?? 0)) / $attendanceTotal * 100),
            'pathways' => $pathways,
            'todaySchedule' => $todaySchedule,
            'recentAdmissions' => AdmissionRequest::with('course')->latest()->take(5)->get(),
            'overdueInvoices' => Invoice::with('student')->open()->where('due_date', '<', now())->orderBy('due_date')->take(5)->get(),
            'activity' => ActivityLog::with('user')->latest('created_at')->take(6)->get(),
            'batches' => Batch::with(['course', 'teacher'])->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])
                ->where('status', 'active')->take(6)->get(),
        ]);
    }
}
