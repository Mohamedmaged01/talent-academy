<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = ['exam_id', 'student_id', 'score', 'remarks'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function passed(): bool
    {
        return (float) $this->score >= $this->exam->pass_score;
    }

    public function percent(): float
    {
        return $this->exam->max_score > 0 ? round($this->score / $this->exam->max_score * 100, 1) : 0;
    }
}
