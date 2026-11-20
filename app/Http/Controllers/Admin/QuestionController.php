<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesQuestions;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Support\Collection;

class QuestionController extends Controller
{
    use HandlesQuestions;

    protected function questionCourses(): Collection
    {
        return self::coursesFor(Course::query());
    }

    protected function canEditQuestion(Question $question): bool
    {
        return auth()->user()->canAdmin('exams', 'manage');
    }

    protected function questionRoutePrefix(): string
    {
        return 'admin.questions';
    }
}
