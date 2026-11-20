<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Exam extends Model
{
    public const TYPES = ['quiz', 'midterm', 'final', 'practical'];

    /** "paper": the teacher types in scores. "online": students sit it in the portal and it grades itself. */
    public const MODES = ['paper', 'online'];

    protected $fillable = [
        'batch_id', 'title', 'type', 'mode', 'instructions', 'exam_date', 'starts_at', 'duration_minutes',
        'closes_at', 'max_score', 'pass_score', 'results_published',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'closes_at' => 'datetime',
            'results_published' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)->withPivot('sort_order')->orderBy('exam_question.sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isOnline(): bool
    {
        return $this->mode === 'online';
    }

    public function opensAt(): Carbon
    {
        $date = $this->exam_date->copy();

        return $this->starts_at ? $date->setTimeFromTimeString($this->starts_at) : $date->startOfDay();
    }

    /** Online exams close at `closes_at`, or at the end of the exam day when none is set. */
    public function closesAt(): ?Carbon
    {
        return $this->closes_at?->copy() ?? $this->exam_date->copy()->endOfDay();
    }

    public function isOpen(): bool
    {
        return $this->isOnline() && now()->between($this->opensAt(), $this->closesAt());
    }

    public function hasClosed(): bool
    {
        return now()->gt($this->closesAt());
    }

    /**
     * Online exams are scored out of the sum of their questions' points. The pass mark
     * keeps the same proportion it had before the questions changed.
     */
    public function syncScoreFromQuestions(): void
    {
        $total = (int) $this->questions()->sum('points');

        if ($total <= 0) {
            return;
        }

        $ratio = $this->max_score > 0 ? $this->pass_score / $this->max_score : 0.5;
        $this->max_score = $total;
        $this->pass_score = (int) round($total * $ratio);
        $this->save();
    }
}
