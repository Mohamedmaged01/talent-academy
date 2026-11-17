<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends TeacherController
{
    public function index(): View
    {
        return view('teacher.exams.index', [
            'exams' => Exam::with('batch.course')
                ->withCount('results')
                ->withAvg('results', 'score')
                ->whereIn('batch_id', $this->myBatches()->pluck('id'))
                ->latest('exam_date')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('teacher.exams.create', [
            'batches' => $this->myBatches()->whereIn('status', ['active', 'upcoming'])->with('course')->get(),
            'selected' => $request->query('batch'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Exam::TYPES)],
            'exam_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'pass_score' => ['required', 'integer', 'min:0', 'lte:max_score'],
        ]);

        $this->authorizeBatch(Batch::findOrFail($data['batch_id']));
        $exam = Exam::create($data);

        return redirect()->route('teacher.exams.show', $exam)->with('success', __('Exam scheduled.'));
    }

    public function show(Exam $exam): View
    {
        $this->authorizeBatch($exam->batch);

        return view('teacher.exams.show', [
            'exam' => $exam->load('batch.course'),
            'students' => $exam->batch->activeStudents()->orderBy('full_name')->get(),
            'results' => $exam->results()->get()->keyBy('student_id'),
        ]);
    }

    public function saveResults(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeBatch($exam->batch);

        $data = $request->validate([
            'scores' => ['array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.$exam->max_score],
            'remarks' => ['array'],
            'remarks.*' => ['nullable', 'string', 'max:255'],
            'publish' => ['boolean'],
        ]);

        $enrolled = $exam->batch->activeStudents()->pluck('students.id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($data, $exam, $enrolled, $request) {
            foreach ($data['scores'] ?? [] as $studentId => $score) {
                if ($score === null || ! in_array((int) $studentId, $enrolled, true)) {
                    continue;
                }

                ExamResult::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $studentId],
                    ['score' => $score, 'remarks' => $data['remarks'][$studentId] ?? null],
                );
            }

            $exam->update(['results_published' => $request->boolean('publish')]);
        });

        ActivityLog::record('exam.results', "Results saved for exam \"{$exam->title}\"", $exam);

        return back()->with('success', $exam->results_published ? __('Results saved and published to students.') : __('Results saved as draft.'));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeBatch($exam->batch);
        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', __('Exam deleted.'));
    }
}
