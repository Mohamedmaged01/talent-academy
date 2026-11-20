<?php

namespace App\Http\Controllers\TeacherPortal;

use App\Http\Controllers\Concerns\HandlesQuestions;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Support\Collection;

/** A teacher works with the question banks of the courses they teach, and edits only their own questions. */
class QuestionController extends TeacherController
{
    use HandlesQuestions;

    protected function questionCourses(): Collection
    {
        return self::coursesFor(Course::whereIn('id', $this->myBatches()->pluck('course_id')));
    }

    protected function canEditQuestion(Question $question): bool
    {
        return (int) $question->created_by === (int) auth()->id();
    }

    protected function questionRoutePrefix(): string
    {
        return 'teacher.questions';
    }
}
