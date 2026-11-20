<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Question;
use App\Services\QuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\BuildsFixtures;
use Tests\TestCase;

class LearningContentTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private function onlineExam($batch, array $questions, array $attributes = []): Exam
    {
        $exam = Exam::create($attributes + [
            'batch_id' => $batch->id, 'title' => 'Quiz', 'type' => 'quiz', 'mode' => 'online', 'exam_date' => today(),
            'starts_at' => '00:00', 'duration_minutes' => 20, 'max_score' => 100, 'pass_score' => 50, 'results_published' => true,
        ]);

        foreach ($questions as $i => $question) {
            $exam->questions()->attach($question->id, ['sort_order' => $i + 1]);
        }
        $exam->syncScoreFromQuestions();

        return $exam->fresh();
    }

    private function questions($course): array
    {
        return [
            Question::create(['course_id' => $course->id, 'type' => 'mcq', 'body' => '2+2?', 'options' => ['3', '4', '5'], 'correct_answer' => '4', 'points' => 2]),
            Question::create(['course_id' => $course->id, 'type' => 'true_false', 'body' => 'Sky is blue', 'correct_answer' => 'true', 'points' => 1]),
            Question::create(['course_id' => $course->id, 'type' => 'short_answer', 'body' => 'Capital of Egypt', 'correct_answer' => 'Cairo', 'points' => 2]),
        ];
    }

    public function test_online_exam_is_scored_from_its_questions_and_keeps_the_pass_ratio(): void
    {
        $batch = $this->makeBatch();
        $exam = $this->onlineExam($batch, $this->questions($batch->course));

        $this->assertSame(5, $exam->max_score);
        $this->assertSame(3, $exam->pass_score); // 50% of 5, rounded
    }

    public function test_student_takes_an_online_exam_and_it_is_graded_on_the_server(): void
    {
        $batch = $this->makeBatch();
        [$mcq, $tf, $short] = $this->questions($batch->course);
        $exam = $this->onlineExam($batch, [$mcq, $tf, $short]);
        $student = $this->makeStudent([$batch]);

        $this->actingAs($student->user)->post(route('student.quizzes.start', $exam))->assertRedirect(route('student.quizzes.show', $exam));
        $this->actingAs($student->user)->get(route('student.quizzes.show', $exam))->assertOk()->assertSee('2+2?');

        $this->actingAs($student->user)->post(route('student.quizzes.submit', $exam), [
            'answers' => [$mcq->id => '4', $tf->id => 'false', $short->id => 'it is cairo'],
        ])->assertRedirect();

        $attempt = ExamAttempt::firstWhere('student_id', $student->id);
        $this->assertSame('submitted', $attempt->status);
        $this->assertEquals(4, (float) $attempt->score); // mcq 2 + short answer 2; true/false wrong
        $this->assertEquals(4, (float) $exam->results()->where('student_id', $student->id)->value('score'));

        // A second submission cannot change the result.
        $this->actingAs($student->user)->post(route('student.quizzes.submit', $exam), ['answers' => [$tf->id => 'true']]);
        $this->assertEquals(4, (float) $attempt->fresh()->score);
    }

    public function test_answers_sent_after_the_time_limit_are_discarded(): void
    {
        $batch = $this->makeBatch();
        [$mcq] = $this->questions($batch->course);
        $exam = $this->onlineExam($batch, [$mcq]);
        $student = $this->makeStudent([$batch]);

        $this->actingAs($student->user)->post(route('student.quizzes.start', $exam));
        Carbon::setTestNow(now()->addMinutes(30));

        $this->actingAs($student->user)->post(route('student.quizzes.submit', $exam), ['answers' => [$mcq->id => '4']]);

        $attempt = ExamAttempt::firstWhere('student_id', $student->id);
        $this->assertSame('expired', $attempt->status);
        $this->assertEquals(0, (float) $attempt->score);
        Carbon::setTestNow();
    }

    public function test_exam_cannot_be_started_before_it_opens_or_by_another_class(): void
    {
        $batch = $this->makeBatch();
        $exam = $this->onlineExam($batch, $this->questions($batch->course), ['exam_date' => today()->addDays(2)]);
        $student = $this->makeStudent([$batch]);
        $outsider = $this->makeStudent([$this->makeBatch()]);

        $this->actingAs($student->user)->post(route('student.quizzes.start', $exam))->assertSessionHasErrors('exam');
        $this->assertDatabaseCount('exam_attempts', 0);

        $this->actingAs($outsider->user)->get(route('student.quizzes.show', $exam))->assertForbidden();
    }

    public function test_questions_are_locked_once_a_student_has_started(): void
    {
        $teacher = $this->makeUser('teacher');
        $batch = $this->makeBatch($teacher);
        [$mcq, $tf] = $this->questions($batch->course);
        $exam = $this->onlineExam($batch, [$mcq]);
        $student = $this->makeStudent([$batch]);
        $this->actingAs($student->user)->post(route('student.quizzes.start', $exam));

        $this->actingAs($teacher)->post(route('teacher.exams.questions.attach', $exam), ['questions' => [$tf->id]])->assertSessionHas('error');
        $this->assertSame(1, $exam->questions()->count());
    }

    public function test_teacher_cannot_add_lectures_or_questions_outside_their_classes(): void
    {
        $teacher = $this->makeUser('teacher');
        $mine = $this->makeBatch($teacher);
        $theirs = $this->makeBatch($this->makeUser('teacher'));

        $this->actingAs($teacher)->post(route('teacher.lectures.store'), ['batch_id' => $theirs->id, 'title' => 'x', 'video_url' => 'https://youtu.be/aircAruvnKk'])->assertForbidden();
        $this->actingAs($teacher)->post(route('teacher.lectures.store'), ['batch_id' => $mine->id, 'title' => 'Intro', 'video_url' => 'https://youtu.be/aircAruvnKk'])->assertRedirect();
        $this->assertDatabaseHas('lectures', ['batch_id' => $mine->id, 'title' => 'Intro']);

        $this->actingAs($teacher)->post(route('teacher.questions.store'), [
            'course_id' => $theirs->course_id, 'type' => 'true_false', 'body' => 'x', 'correct_answer' => 'true', 'points' => 1, 'difficulty' => 'easy',
        ])->assertSessionHasErrors('course_id');
    }

    public function test_student_sees_only_their_class_lectures_and_views_are_tracked(): void
    {
        $batch = $this->makeBatch();
        $lecture = Lecture::create(['batch_id' => $batch->id, 'title' => 'L1', 'video_url' => 'https://www.youtube.com/watch?v=aircAruvnKk']);
        $student = $this->makeStudent([$batch]);
        $outsider = $this->makeStudent([$this->makeBatch()]);

        $this->actingAs($student->user)->get(route('student.lectures.show', $lecture))->assertOk()->assertSee('youtube-nocookie.com/embed/aircAruvnKk', false);
        $this->actingAs($student->user)->get(route('student.lectures.show', $lecture));
        $this->assertSame(2, $lecture->views()->first()->views);

        $this->actingAs($outsider->user)->get(route('student.lectures.show', $lecture))->assertForbidden();
    }

    public function test_question_import_parses_types_letters_and_reports_bad_lines(): void
    {
        $result = app(QuestionImporter::class)->parse(implode("\n", [
            '# comment line',
            'What is 2+2? | 3; 4; 5 | B | 2',
            'The earth is round | | صح',
            'Capital of Egypt | | Cairo | 3',
            'Broken line without answer',
            'Pick one | A; B | Z',
        ]));

        $this->assertCount(3, $result['questions']);
        $this->assertSame(['mcq', '4', 2], [$result['questions'][0]['type'], $result['questions'][0]['correct_answer'], $result['questions'][0]['points']]);
        $this->assertSame(['true_false', 'true'], [$result['questions'][1]['type'], $result['questions'][1]['correct_answer']]);
        $this->assertSame('short_answer', $result['questions'][2]['type']);
        $this->assertCount(2, $result['errors']);
    }

    public function test_downloads_respect_their_audience(): void
    {
        $teachersOnly = Download::create(['title_ar' => 'للمعلمين', 'category' => 'forms', 'audience' => 'teachers', 'url' => 'https://example.com/file']);

        $this->get(route('downloads.open', $teachersOnly))->assertNotFound();
        $this->actingAs($this->makeStudent()->user)->get(route('downloads.open', $teachersOnly))->assertNotFound();
        $this->actingAs($this->makeUser('teacher'))->get(route('downloads.open', $teachersOnly))->assertRedirect('https://example.com/file');
    }
}
