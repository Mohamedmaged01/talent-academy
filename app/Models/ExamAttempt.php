<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** One student's sitting of an online exam. */
class ExamAttempt extends Model
{
    /** Seconds allowed after the timer runs out, to absorb slow connections on submit. */
    public const GRACE_SECONDS = 120;

    protected $fillable = ['exam_id', 'student_id', 'started_at', 'submitted_at', 'answers', 'score', 'status'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'answers' => 'array',
            'score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** When the timer ends: the exam duration from start, capped by the exam's closing time. */
    public function deadline(): Carbon
    {
        $byDuration = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
        $closes = $this->exam->closesAt();

        return $closes && $closes->lt($byDuration) ? $closes : $byDuration;
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function hasExpired(): bool
    {
        return now()->gt($this->deadline()->copy()->addSeconds(self::GRACE_SECONDS));
    }
}
