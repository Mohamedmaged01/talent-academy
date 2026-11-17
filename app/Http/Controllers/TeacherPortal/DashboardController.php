<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\BatchSchedule;
use App\Models\Enrollment;
use App\Models\Exam;
use Illuminate\View\View;

class DashboardController extends TeacherController
{
    public function __invoke(): View
    {
        $batchIds = $this->myBatches()->whereIn('status', ['active', 'upcoming'])->pluck('id');

        $todayClasses = BatchSchedule::with('batch.course')
            ->whereIn('batch_id', $batchIds)
            ->where('day_of_week', now()->dayOfWeek)
            ->orderBy('starts_at')
            ->get();

        $toGrade = AssignmentSubmission::whereNull('graded_at')
            ->whereHas('assignment', fn ($q) => $q->whereIn('batch_id', $batchIds))
            ->count();

        $assignments = Assignment::whereIn('batch_id', $batchIds)->withCount('submissions')->with('batch')->latest('due_at')->take(5)->get();

        $submitted = AssignmentSubmission::whereHas('assignment', fn ($q) => $q->whereIn('batch_id', $batchIds))->count();
        $expected = Assignment::whereIn('batch_id', $batchIds)->get()->sum(fn ($a) => Enrollment::where('batch_id', $a->batch_id)->where('status', 'active')->count());

        return view('teacher.dashboard', [
            'batches' => $this->myBatches()->whereIn('status', ['active', 'upcoming'])->with(['course', 'schedules'])
                ->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])->get(),
            'todayClasses' => $todayClasses,
            'studentCount' => Enrollment::whereIn('batch_id', $batchIds)->where('status', 'active')->distinct('student_id')->count('student_id'),
            'toGrade' => $toGrade,
            'upcomingExams' => Exam::with('batch')->whereIn('batch_id', $batchIds)->where('exam_date', '>=', now()->toDateString())->orderBy('exam_date')->take(5)->get(),
            'assignments' => $assignments,
            'submissionRate' => $expected > 0 ? round($submitted / $expected * 100) : 0,
            'announcements' => Announcement::visibleTo(auth()->user())->with('author')->latest()->take(4)->get(),
        ]);
    }

    public function announcements(): View
    {
        return view('portal.announcements', [
            'announcements' => Announcement::visibleTo(auth()->user())->with(['author', 'batch'])->orderByDesc('is_pinned')->latest()->paginate(15),
        ]);
    }
}
