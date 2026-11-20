<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionRequest extends Model
{
    use Concerns\ScopedToBranch;

    public const STATUSES = ['pending', 'contacted', 'accepted', 'rejected'];

    protected $fillable = [
        'reference', 'full_name', 'phone', 'email', 'pathway', 'course_id', 'branch_id',
        'grade_level', 'guardian_name', 'guardian_phone', 'notes', 'status',
        'reviewed_by', 'reviewed_at', 'student_id',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public static function newReference(): string
    {
        do {
            $ref = 'ADM-'.now()->format('ymd').'-'.random_int(1000, 9999);
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
