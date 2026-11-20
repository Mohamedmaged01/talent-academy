<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TeacherPortal\ExamController as TeacherExamController;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\Lecture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Academy-wide view of exams (paper and online) and recorded lectures. Teachers create them; admins oversee. */
class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $exams = Exam::with('batch.course', 'batch.teacher')->withCount(['results', 'questions', 'attempts'])->withAvg('results', 'score')
            ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', $v))
            ->when($request->query('mode'), fn ($q, $v) => $q->where('mode', $v))
            ->when($request->query('status') === 'published', fn ($q) => $q->where('results_published', true))
            ->when($request->query('status') === 'draft', fn ($q) => $q->where('results_published', false))
            ->latest('exam_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.exams.index', [
            'exams' => $exams,
            'batches' => Batch::orderBy('name')->get(['id', 'name']),
            'counts' => [
                'upcoming' => Exam::where('exam_date', '>=', today())->count(),
                'online' => Exam::where('mode', 'online')->count(),
                'unpublished' => Exam::where('results_published', false)->where('exam_date', '<', today())->count(),
            ],
        ]);
    }

    public function show(Exam $exam): View
    {
        $exam->load(['batch.course', 'batch.teacher', 'questions']);

        return view('admin.exams.show', [
            'exam' => $exam,
            'students' => $exam->batch->activeStudents()->orderBy('full_name')->get(),
            'results' => $exam->results()->get()->keyBy('student_id'),
            'attempts' => $exam->attempts()->get()->keyBy('student_id'),
        ]);
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $exam->update(['results_published' => ! $exam->results_published]);
        ActivityLog::record('exam.publish', "Results for \"{$exam->title}\" ".($exam->results_published ? 'published' : 'hidden'), $exam);

        if ($exam->results_published) {
            TeacherExamController::announceResults($exam);
        }

        return back()->with('success', $exam->results_published ? __('Results published.') : __('Results hidden from students and parents.'));
    }

    public function lectures(Request $request): View
    {
        return view('admin.lectures.index', [
            'lectures' => Lecture::with(['batch.course', 'teacher'])->withCount('views')
                ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', $v))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'batches' => Batch::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function destroyLecture(Lecture $lecture): RedirectResponse
    {
        ActivityLog::record('lecture.deleted', "Lecture \"{$lecture->title}\" removed by an administrator");
        $lecture->delete();

        return back()->with('success', __('Lecture removed.'));
    }
}
