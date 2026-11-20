<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Batch extends Model
{
    use Concerns\ScopedToBranch, HasFactory;

    public const STATUSES = ['upcoming', 'active', 'completed', 'cancelled'];

    protected $fillable = [
        'branch_id', 'course_id', 'teacher_id', 'code', 'name', 'room', 'meeting_url',
        'capacity', 'start_date', 'end_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(BatchSchedule::class)->orderBy('day_of_week')->orderBy('starts_at');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class)->orderBy('sort_order')->orderBy('id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(CertificateNomination::class);
    }

    /**
     * The next (or current) session of this class, from its weekly schedule.
     *
     * @return array{starts: Carbon, ends: Carbon}|null
     */
    public function nextSession(): ?array
    {
        $now = now();
        $best = null;

        foreach ($this->schedules as $slot) {
            for ($offset = 0; $offset <= 7; $offset++) {
                $day = $now->copy()->startOfDay()->addDays($offset);

                if ($day->dayOfWeek !== (int) $slot->day_of_week) {
                    continue;
                }

                $starts = $day->copy()->setTimeFromTimeString($slot->starts_at);
                $ends = $day->copy()->setTimeFromTimeString($slot->ends_at);

                if ($ends->gt($now) && (! $best || $starts->lt($best['starts']))) {
                    $best = ['starts' => $starts, 'ends' => $ends];
                }
            }
        }

        return $best;
    }

    /** A live session can be joined from 15 minutes before it starts until it ends. */
    public function isLiveNow(): bool
    {
        $session = $this->nextSession();

        return $this->meeting_url && $session && now()->between($session['starts']->copy()->subMinutes(15), $session['ends']);
    }

    public function isFull(): bool
    {
        return $this->enrollments()->where('status', 'active')->count() >= $this->capacity;
    }

    public function scheduleSummary(): string
    {
        return $this->schedules
            ->map(fn (BatchSchedule $s) => $s->dayName().' '.$s->timeRange())
            ->implode(' · ');
    }
}
