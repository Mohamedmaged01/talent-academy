<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Student;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Online exams: students start an attempt inside the exam window, answer within
 * the time limit, and the server grades the answers against the question bank.
 * Scores land in exam_results, so they flow into grades, certificates and reports
 * exactly like paper exam results.
 */
class QuizService
{
    public function start(Exam $exam, Student $student): ExamAttempt
    {
        if (! $exam->isOnline()) {
            throw ValidationException::withMessages(['exam' => __('This exam is not held online.')]);
        }

        $existing = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->first();

        if ($existing) {
            return $existing;
        }

        if (! $exam->isOpen()) {
            throw ValidationException::withMessages(['exam' => __('This exam is not open right now.')]);
        }

        if (! $exam->questions()->exists()) {
            throw ValidationException::withMessages(['exam' => __('This exam has no questions yet.')]);
        }

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Grade and close an attempt. Answers that arrive after the time limit plus a
     * short grace period are discarded and the attempt is closed as expired.
     *
     * @param  array<int|string, string|null>  $answers  question id => answer
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        if ($attempt->hasExpired()) {
            return $this->expire($attempt);
        }

        return $this->finalize($attempt, $answers, 'submitted');
    }

    /** Close an attempt whose time ran out without a submission. */
    public function expire(ExamAttempt $attempt): ExamAttempt
    {
        return $this->finalize($attempt, $attempt->answers ?? [], 'expired');
    }

    private function finalize(ExamAttempt $attempt, array $answers, string $status): ExamAttempt
    {
        $exam = $attempt->exam;
        $questions = $exam->questions()->get();

        $clean = [];
        $score = 0;

        foreach ($questions as $question) {
            $answer = isset($answers[$question->id]) ? mb_substr(trim((string) $answers[$question->id]), 0, 500) : null;
            $clean[$question->id] = $answer;

            if ($question->isCorrect($answer)) {
                $score += $question->points;
            }
        }

        DB::transaction(function () use ($attempt, $clean, $score, $status, $exam) {
            $attempt->update([
                'answers' => $clean,
                'score' => $score,
                'status' => $status,
                'submitted_at' => now(),
            ]);

            ExamResult::updateOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $attempt->student_id],
                ['score' => $score, 'remarks' => $status === 'expired' ? __('Time expired') : __('Auto-graded')],
            );
        });

        if ($exam->results_published) {
            Notify::student($attempt->student, 'Your result for ":exam" is out: :score / :max', [
                'exam' => $exam->title, 'score' => $score, 'max' => $exam->max_score,
            ], route('student.quizzes.show', $exam), 'file-check');
        }

        return $attempt->refresh();
    }
}
