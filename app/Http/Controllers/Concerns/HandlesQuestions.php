<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\Question;
use App\Services\QuestionImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Question-bank screens shared by the teacher portal and the admin back office.
 * The using controller decides which courses are in reach and which questions
 * the user may change.
 */
trait HandlesQuestions
{
    /** Courses whose question banks this user can open. */
    abstract protected function questionCourses(): Collection;

    abstract protected function canEditQuestion(Question $question): bool;

    abstract protected function questionRoutePrefix(): string;

    public function index(Request $request): View
    {
        $courses = $this->questionCourses();

        return view('questions.index', [
            'questions' => Question::with(['course', 'author'])->withCount('exams')
                ->whereIn('course_id', $courses->pluck('id'))
                ->when($request->query('course'), fn (Builder $q, $v) => $q->where('course_id', $v))
                ->when($request->query('type'), fn (Builder $q, $v) => $q->where('type', $v))
                ->when($request->query('q'), fn (Builder $q, $v) => $q->where('body', 'like', "%{$v}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'courses' => $courses,
            'prefix' => $this->questionRoutePrefix(),
            'canEdit' => fn (Question $q) => $this->canEditQuestion($q),
        ]);
    }

    public function create(Request $request): View
    {
        return view('questions.form', [
            'question' => new Question(['type' => 'mcq', 'points' => 1, 'difficulty' => 'medium', 'course_id' => $request->query('course')]),
            'courses' => $this->questionCourses(),
            'prefix' => $this->questionRoutePrefix(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateQuestion($request);
        $question = Question::create($data + ['created_by' => auth()->id()]);
        ActivityLog::record('question.created', "Question #{$question->id} added to the bank", $question);

        return redirect()->route($this->questionRoutePrefix().'.index', ['course' => $question->course_id])
            ->with('success', __('Question added to the bank.'));
    }

    public function edit(Question $question): View
    {
        abort_unless($this->canEditQuestion($question), 403);

        return view('questions.form', [
            'question' => $question,
            'courses' => $this->questionCourses(),
            'prefix' => $this->questionRoutePrefix(),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        abort_unless($this->canEditQuestion($question), 403);

        if ($question->exams()->whereHas('attempts')->exists()) {
            return back()->with('error', __('Students have already answered this question in an exam, so it can no longer be changed.'));
        }

        $question->update($this->validateQuestion($request));

        return redirect()->route($this->questionRoutePrefix().'.index', ['course' => $question->course_id])
            ->with('success', __('Question updated.'));
    }

    public function destroy(Question $question): RedirectResponse
    {
        abort_unless($this->canEditQuestion($question), 403);

        if ($question->exams()->exists()) {
            return back()->with('error', __('This question is used in an exam. Remove it from the exam first.'));
        }

        $question->delete();

        return back()->with('success', __('Question deleted.'));
    }

    public function import(Request $request, QuestionImporter $importer): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', Rule::in($this->questionCourses()->pluck('id')->all())],
            'file' => ['nullable', 'required_without:text', 'file', 'max:1024', 'mimes:txt,csv'],
            'text' => ['nullable', 'required_without:file', 'string', 'max:100000'],
        ]);

        $text = $request->hasFile('file') ? (string) file_get_contents($request->file('file')->getRealPath()) : (string) $request->input('text');
        ['questions' => $rows, 'errors' => $errors] = $importer->parse($text);

        if ($rows === []) {
            throw ValidationException::withMessages(['text' => $errors ?: [__('No questions found in the file.')]]);
        }

        foreach ($rows as $row) {
            Question::create($row + ['course_id' => $request->integer('course_id'), 'created_by' => auth()->id()]);
        }

        ActivityLog::record('question.imported', count($rows).' questions imported into course #'.$request->integer('course_id'));

        $message = __(':count question(s) imported.', ['count' => count($rows)]);

        return redirect()->route($this->questionRoutePrefix().'.index', ['course' => $request->integer('course_id')])
            ->with('success', $message)
            ->with($errors ? 'warning' : 'info', $errors ? __('Skipped lines: :lines', ['lines' => implode(' · ', array_slice($errors, 0, 8))]) : null);
    }

    private function validateQuestion(Request $request): array
    {
        $data = $request->validate([
            'course_id' => ['required', Rule::in($this->questionCourses()->pluck('id')->all())],
            'type' => ['required', Rule::in(Question::TYPES)],
            'body' => ['required', 'string', 'max:2000'],
            'options' => ['nullable', 'array', 'max:8'],
            'options.*' => ['nullable', 'string', 'max:250'],
            'correct_answer' => ['required', 'string', 'max:250'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'difficulty' => ['required', Rule::in(Question::DIFFICULTIES)],
            'explanation' => ['nullable', 'string', 'max:1000'],
        ]);

        $options = array_values(array_filter(array_map('trim', $data['options'] ?? []), fn ($o) => $o !== ''));

        if ($data['type'] === 'mcq') {
            if (count($options) < 2) {
                throw ValidationException::withMessages(['options' => __('A multiple-choice question needs at least two options.')]);
            }

            if (! in_array(Question::normalize($data['correct_answer']), array_map([Question::class, 'normalize'], $options), true)) {
                throw ValidationException::withMessages(['correct_answer' => __('The correct answer must be one of the options.')]);
            }
        }

        if ($data['type'] === 'true_false' && ! in_array($data['correct_answer'], ['true', 'false'], true)) {
            throw ValidationException::withMessages(['correct_answer' => __('Choose true or false.')]);
        }

        $data['options'] = $data['type'] === 'mcq' ? $options : null;

        return $data;
    }

    protected static function coursesFor(Builder $query): Collection
    {
        return $query->orderBy('pathway')->orderBy('sort_order')->get();
    }
}
