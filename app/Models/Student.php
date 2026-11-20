<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use Concerns\ScopedToBranch, HasFactory;

    public const STATUSES = ['active', 'suspended', 'graduated', 'withdrawn'];

    protected $fillable = [
        'user_id', 'branch_id', 'code', 'full_name', 'full_name_en', 'national_id',
        'gender', 'birth_date', 'phone', 'email', 'pathway', 'grade_level',
        'school_name', 'address', 'status', 'enrolled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrolled_at' => 'date',
        ];
    }

    public static function nextCode(): string
    {
        $prefix = 'TA-'.now()->format('Y').'-';
        $last = static::where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'en' && $this->full_name_en ? $this->full_name_en : $this->full_name;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'enrollments')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** Teachers' and administrators' notes on the file (the `notes` column is the admin's free-text remark). */
    public function fileNotes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /** Percentage of sessions attended (present or late count as attended). */
    public function attendanceRate(?int $batchId = null): ?float
    {
        $query = $this->attendance()->when($batchId, fn ($q) => $q->where('batch_id', $batchId));
        $total = (clone $query)->count();

        if ($total === 0) {
            return null;
        }

        $attended = (clone $query)->whereIn('status', ['present', 'late'])->count();

        return round($attended / $total * 100, 1);
    }

    /** Average exam score as a percentage of each exam's max score (published results only). */
    public function averageExamPercent(?int $batchId = null): ?float
    {
        $results = $this->examResults()
            ->with('exam')
            ->whereHas('exam', fn ($q) => $q->where('results_published', true)
                ->when($batchId, fn ($q) => $q->where('batch_id', $batchId)))
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        return round($results->avg(fn ($r) => $r->exam->max_score > 0 ? $r->score / $r->exam->max_score * 100 : 0), 1);
    }

    public function outstandingBalance(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->sum(fn (Invoice $i) => $i->balance());
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('full_name_en', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
