<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    public const TYPES = ['quiz', 'midterm', 'final', 'practical'];

    protected $fillable = [
        'batch_id', 'title', 'type', 'exam_date', 'starts_at', 'duration_minutes',
        'max_score', 'pass_score', 'results_published',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
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
}
