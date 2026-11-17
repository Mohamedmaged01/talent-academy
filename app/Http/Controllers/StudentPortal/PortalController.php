<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\BatchSchedule;
use App\Models\Certificate;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\View\View;

class PortalController extends Controller
{
    public static function currentStudent(): Student
    {
        $student = auth()->user()->studentRecord;
        abort_unless($student, 403, __('No student record is linked to this account.'));

        return $student;
    }

    public function dashboard(): View
    {
        $student = self::currentStudent();
        $batchIds = $student->batches()->wherePivot('status', 'active')->pluck('batches.id');

        return view('student.dashboard', [
            'student' => $student->load('branch'),
            'batches' => $student->batches()->wherePivot('status', 'active')->with(['course', 'teacher', 'schedules'])->get(),
            'attendanceRate' => $student->attendanceRate(),
            'average' => $student->averageExamPercent(),
            'balance' => $student->outstandingBalance(),
            'todayClasses' => BatchSchedule::with('batch.course')->whereIn('batch_id', $batchIds)->where('day_of_week', now()->dayOfWeek)->orderBy('starts_at')->get(),
            'pendingAssignments' => Assignment::with('batch')->whereIn('batch_id', $batchIds)
                ->where('due_at', '>=', now())
                ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $student->id))
                ->orderBy('due_at')->take(5)->get(),
            'upcomingExams' => Exam::with('batch')->whereIn('batch_id', $batchIds)->where('exam_date', '>=', now()->toDateString())->orderBy('exam_date')->take(4)->get(),
            'recentResults' => $student->examResults()->with('exam.batch')->whereHas('exam', fn ($q) => $q->where('results_published', true))->latest()->take(5)->get(),
            'announcements' => Announcement::visibleTo(auth()->user())->with('author')->orderByDesc('is_pinned')->latest()->take(4)->get(),
            'certificatesCount' => $student->certificates()->where('status', 'valid')->count(),
        ]);
    }

    public function schedule(): View
    {
        $student = self::currentStudent();

        $slots = BatchSchedule::with(['batch.course', 'batch.teacher'])
            ->whereIn('batch_id', $student->batches()->wherePivot('status', 'active')->pluck('batches.id'))
            ->orderBy('starts_at')
            ->get()
            ->groupBy('day_of_week');

        return view('portal.schedule', ['slots' => $slots, 'days' => BatchSchedule::DAYS, 'showTeacher' => true]);
    }

    public function courses(): View
    {
        $student = self::currentStudent();

        return view('student.courses', [
            'batches' => $student->batches()->with(['course', 'teacher', 'schedules'])->withCount('materials')->get()
                ->map(fn (Batch $b) => ['batch' => $b, 'attendance' => $student->attendanceRate($b->id), 'average' => $student->averageExamPercent($b->id)]),
        ]);
    }

    public function course(Batch $batch): View
    {
        $student = self::currentStudent();
        abort_unless($student->batches()->whereKey($batch->id)->exists(), 403);

        $batch->load(['course', 'teacher.teacherProfile', 'schedules', 'materials' => fn ($q) => $q->latest()]);

        return view('student.course', [
            'batch' => $batch,
            'assignments' => $batch->assignments()->latest('due_at')->get(),
            'submissions' => $student->submissions()->whereIn('assignment_id', $batch->assignments()->pluck('id'))->get()->keyBy('assignment_id'),
            'attendance' => $student->attendanceRate($batch->id),
            'average' => $student->averageExamPercent($batch->id),
        ]);
    }

    public function grades(): View
    {
        $student = self::currentStudent();

        return view('student.grades', [
            'results' => $student->examResults()->with('exam.batch.course')
                ->whereHas('exam', fn ($q) => $q->where('results_published', true))
                ->get()->sortByDesc(fn ($r) => $r->exam->exam_date),
            'submissions' => $student->submissions()->with('assignment.batch')->whereNotNull('graded_at')->latest('graded_at')->get(),
            'average' => $student->averageExamPercent(),
        ]);
    }

    public function attendance(): View
    {
        $student = self::currentStudent();
        $records = $student->attendance()->with('batch.course')->latest('date')->get();

        return view('student.attendance', [
            'records' => $records,
            'rate' => $student->attendanceRate(),
            'counts' => $records->countBy('status'),
        ]);
    }

    public function payments(): View
    {
        $student = self::currentStudent();

        return view('student.payments', [
            'invoices' => $student->invoices()->with('payments')->where('status', '!=', 'cancelled')->orderBy('due_date')->get(),
            'payments' => $student->payments()->with('invoice')->latest('paid_at')->get(),
            'balance' => $student->outstandingBalance(),
        ]);
    }

    public function certificates(): View
    {
        return view('student.certificates', [
            'certificates' => self::currentStudent()->certificates()->with('course')->latest('issued_at')->get(),
        ]);
    }

    public function certificate(Certificate $certificate): View
    {
        abort_unless((int) $certificate->student_id === (int) self::currentStudent()->id, 403);

        return view('certificates.print', ['certificate' => $certificate->load(['student', 'course', 'batch.teacher'])]);
    }

    public function announcements(): View
    {
        return view('portal.announcements', [
            'announcements' => Announcement::visibleTo(auth()->user())->with(['author', 'batch'])->orderByDesc('is_pinned')->latest()->paginate(15),
        ]);
    }
}
