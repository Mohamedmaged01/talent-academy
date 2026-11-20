<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Support\Notify;
use App\Support\Webhooks;
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
            'mode' => ['required', Rule::in(Exam::MODES)],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'exam_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date', 'after:exam_date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'pass_score' => ['required', 'integer', 'min:0', 'lte:max_score'],
        ]);

        $this->authorizeBatch(Batch::findOrFail($data['batch_id']));
        $exam = Exam::create($data + ['results_published' => $data['mode'] === 'online' && $request->boolean('publish_immediately')]);

        return redirect()->route('teacher.exams.show', $exam)->with('success', $exam->isOnline()
            ? __('Online exam created. Now add questions from the question bank.')
            : __('Exam scheduled.'));
    }

    public function show(Exam $exam): View
    {
        $this->authorizeBatch($exam->batch);
        $exam->load(['batch.course', 'questions']);

        return view('teacher.exams.show', [
            'exam' => $exam,
            'students' => $exam->batch->activeStudents()->orderBy('full_name')->get(),
            'results' => $exam->results()->get()->keyBy('student_id'),
            'attempts' => $exam->attempts()->get()->keyBy('student_id'),
            'bank' => $exam->isOnline()
                ? Question::where('course_id', $exam->batch->course_id)->whereNotIn('id', $exam->questions->pluck('id'))->latest()->get()
                : collect(),
        ]);
    }

    /** Add question-bank questions to an online exam. Locked once a student has started it. */
    public function attachQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeBatch($exam->batch);
        abort_unless($exam->isOnline(), 404);

        if ($exam->attempts()->exists()) {
            return back()->with('error', __('Students have started this exam, so its questions are locked.'));
        }

        $data = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', Rule::exists('questions', 'id')->where('course_id', $exam->batch->course_id)],
        ]);

        $next = (int) $exam->questions()->max('exam_question.sort_order');
        foreach (array_unique($data['questions']) as $questionId) {
            $exam->questions()->syncWithoutDetaching([$questionId => ['sort_order' => ++$next]]);
        }

        $exam->syncScoreFromQuestions();

        return back()->with('success', __('Questions added. The exam is now out of :max.', ['max' => $exam->max_score]));
    }

    public function detachQuestion(Exam $exam, Question $question): RedirectResponse
    {
        $this->authorizeBatch($exam->batch);

        if ($exam->attempts()->exists()) {
            return back()->with('error', __('Students have started this exam, so its questions are locked.'));
        }

        $exam->questions()->detach($question->id);
        $exam->syncScoreFromQuestions();

        return back()->with('success', __('Question removed from the exam.'));
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
        $wasPublished = $exam->results_published;

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

        if (! $wasPublished && $exam->results_published) {
            $this->announceResults($exam);
        }

        return back()->with('success', $exam->results_published ? __('Results saved and published to students.') : __('Results saved as draft.'));
    }

    /** Tell students and parents the results are out, and notify webhooks. */
    public static function announceResults(Exam $exam): void
    {
        $exam->loadMissing('batch');

        foreach ($exam->results()->with('student.user', 'student.guardians')->get() as $result) {
            Notify::student($result->student, 'Results published for ":exam" (:class)', ['exam' => $exam->title, 'class' => $exam->batch->name],
                route('student.grades'), 'file-check', route('parent.children.show', $result->student));
        }

        Webhooks::dispatch('exam.results_published', [
            'exam' => $exam->title, 'class_code' => $exam->batch->code, 'date' => $exam->exam_date->toDateString(),
            'results' => $exam->results()->count(), 'max_score' => $exam->max_score,
        ]);
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeBatch($exam->batch);
        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', __('Exam deleted.'));
    }
}
