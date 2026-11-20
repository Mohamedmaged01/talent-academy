<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A question-bank item, owned by a course and reusable across that course's online exams. */
class Question extends Model
{
    public const TYPES = ['mcq', 'true_false', 'short_answer'];

    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    protected $fillable = ['course_id', 'created_by', 'type', 'body', 'options', 'correct_answer', 'points', 'difficulty', 'explanation'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class)->withPivot('sort_order');
    }

    /** Choices shown to the student: the stored options, or true/false. */
    public function choices(): array
    {
        return match ($this->type) {
            'true_false' => ['true', 'false'],
            'mcq' => array_values($this->options ?? []),
            default => [],
        };
    }

    public function isCorrect(?string $answer): bool
    {
        $given = self::normalize($answer);
        $expected = self::normalize($this->correct_answer);

        if ($given === '' || $expected === '') {
            return false;
        }

        // Short answers accept the expected phrase anywhere in the response (e.g. "it is SQL" for "sql").
        return $this->type === 'short_answer'
            ? $given === $expected || str_contains($given, $expected)
            : $given === $expected;
    }

    public static function normalize(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
    }
}
