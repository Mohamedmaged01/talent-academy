<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Lecture;
use App\Models\LectureView;
use Illuminate\View\View;

/** Recorded lectures and live online sessions for the signed-in student. */
class LearningController extends Controller
{
    public function lectures(): View
    {
        $student = PortalController::currentStudent();
        $batches = $student->batches()->with('course')->get();

        $lectures = Lecture::published()->with('batch.course')
            ->whereIn('batch_id', $batches->pluck('id'))
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->groupBy('batch_id');

        return view('student.lectures.index', [
            'batches' => $batches->filter(fn (Batch $b) => $lectures->has($b->id)),
            'lectures' => $lectures,
            'watched' => LectureView::where('student_id', $student->id)->pluck('lecture_id')->flip(),
        ]);
    }

    public function lecture(Lecture $lecture): View
    {
        $student = PortalController::currentStudent();
        abort_unless($lecture->is_published && $student->batches()->whereKey($lecture->batch_id)->exists(), 403);

        LectureView::record($lecture, $student);

        return view('student.lectures.show', [
            'lecture' => $lecture->load('batch.course', 'teacher'),
            'playlist' => $lecture->batch->lectures()->published()->get(),
            'watched' => LectureView::where('student_id', $student->id)->pluck('lecture_id')->flip(),
        ]);
    }

    public function live(): View
    {
        $student = PortalController::currentStudent();

        $batches = $student->batches()->wherePivot('status', 'active')
            ->whereIn('batches.status', ['active', 'upcoming'])
            ->with(['course', 'teacher', 'schedules'])
            ->get()
            ->map(fn (Batch $batch) => ['batch' => $batch, 'session' => $batch->nextSession(), 'live' => $batch->isLiveNow()])
            ->sortBy(fn ($row) => $row['session']['starts'] ?? now()->addYear())
            ->values();

        return view('student.live', ['rows' => $batches]);
    }
}
