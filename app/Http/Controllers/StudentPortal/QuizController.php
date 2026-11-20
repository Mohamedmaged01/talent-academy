<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Online exams: list, sit (timed) and review once results are published. */
class QuizController extends Controller
{
    public function __construct(private QuizService $quizzes) {}

    public function index(): View
    {
        $student = PortalController::currentStudent();
        $attempts = ExamAttempt::where('student_id', $student->id)->get()->keyBy('exam_id');

        $exams = Exam::with('batch.course')->withCount('questions')
            ->where('mode', 'online')
            ->whereIn('batch_id', $student->batches()->wherePivot('status', 'active')->pluck('batches.id'))
            ->orderByDesc('exam_date')
            ->get()
            ->map(fn (Exam $exam) => ['exam' => $exam, 'attempt' => $attempts->get($exam->id), 'state' => $this->state($exam, $attempts->get($exam->id))]);

        return view('student.quizzes.index', [
            'open' => $exams->whereIn('state', ['open', 'in_progress'])->values(),
            'upcoming' => $exams->where('state', 'upcoming')->sortBy(fn ($r) => $r['exam']->opensAt())->values(),
            'past' => $exams->whereIn('state', ['submitted', 'expired', 'missed'])->values(),
        ]);
    }

    public function show(Exam $exam): View|RedirectResponse
    {
        $student = $this->authorizeExam($exam);
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->first();

        // A student who closed the tab and came back after the time limit gets the attempt closed as expired.
        if ($attempt?->isInProgress() && $attempt->hasExpired()) {
            $attempt = $this->quizzes->expire($attempt);
        }

        $exam->load(['batch.course', 'questions']);

        return view('student.quizzes.show', [
            'exam' => $exam,
            'attempt' => $attempt,
            'state' => $this->state($exam, $attempt),
            'result' => $exam->results()->where('student_id', $student->id)->first(),
        ]);
    }

    public function start(Exam $exam): RedirectResponse
    {
        $student = $this->authorizeExam($exam);
        $this->quizzes->start($exam, $student);

        return redirect()->route('student.quizzes.show', $exam);
    }

    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $student = $this->authorizeExam($exam);
        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->firstOrFail();

        $request->validate(['answers' => ['nullable', 'array'], 'answers.*' => ['nullable', 'string', 'max:500']]);
        $attempt = $this->quizzes->submit($attempt, $request->input('answers', []));

        return redirect()->route('student.quizzes.show', $exam)->with(
            $attempt->status === 'expired' ? 'warning' : 'success',
            $attempt->status === 'expired' ? __('The time limit had passed, so your answers could not be accepted.') : __('Your answers were submitted.'),
        );
    }

    private function authorizeExam(Exam $exam): Student
    {
        $student = PortalController::currentStudent();

        abort_unless($exam->isOnline() && $student->batches()->wherePivot('status', 'active')->whereKey($exam->batch_id)->exists(), 403);

        return $student;
    }

    private function state(Exam $exam, ?ExamAttempt $attempt): string
    {
        return match (true) {
            $attempt && $attempt->isInProgress() => 'in_progress',
            $attempt !== null => $attempt->status,
            $exam->isOpen() => 'open',
            $exam->hasClosed() => 'missed',
            default => 'upcoming',
        };
    }
}
