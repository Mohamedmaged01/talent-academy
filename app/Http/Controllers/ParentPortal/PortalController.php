<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\BatchSchedule;
use App\Models\Student;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $children = auth()->user()->children()->with(['branch', 'batches.course'])->get()
            ->map(fn (Student $child) => [
                'student' => $child,
                'attendance' => $child->attendanceRate(),
                'average' => $child->averageExamPercent(),
                'balance' => $child->outstandingBalance(),
                'absences' => $child->attendance()->where('status', 'absent')->where('date', '>=', now()->subDays(30))->count(),
            ]);

        return view('parent.dashboard', [
            'children' => $children,
            'announcements' => Announcement::visibleTo(auth()->user())->with('author')->orderByDesc('is_pinned')->latest()->take(5)->get(),
        ]);
    }

    public function child(Student $student): View
    {
        abort_unless(auth()->user()->children()->whereKey($student->id)->exists(), 403);

        $batchIds = $student->batches()->wherePivot('status', 'active')->pluck('batches.id');

        return view('parent.child', [
            'student' => $student->load(['branch', 'batches' => fn ($q) => $q->with(['course', 'teacher', 'schedules'])]),
            'attendance' => $student->attendanceRate(),
            'average' => $student->averageExamPercent(),
            'balance' => $student->outstandingBalance(),
            'recentAttendance' => $student->attendance()->with('batch')->latest('date')->take(15)->get(),
            'results' => $student->examResults()->with('exam.batch')->whereHas('exam', fn ($q) => $q->where('results_published', true))->latest()->take(10)->get(),
            'invoices' => $student->invoices()->where('status', '!=', 'cancelled')->orderBy('due_date')->get(),
            'payments' => $student->payments()->latest('paid_at')->take(10)->get(),
            'slots' => BatchSchedule::with('batch.course')->whereIn('batch_id', $batchIds)->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'certificates' => $student->certificates()->where('status', 'valid')->get(),
        ]);
    }

    public function announcements(): View
    {
        return view('portal.announcements', [
            'announcements' => Announcement::visibleTo(auth()->user())->with(['author', 'batch'])->orderByDesc('is_pinned')->latest()->paginate(15),
        ]);
    }
}
