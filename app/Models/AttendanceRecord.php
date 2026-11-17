<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = ['batch_id', 'student_id', 'date', 'status', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /**
     * Always store the plain Y-m-d date. Without this, the `date` cast writes
     * "Y-m-d 00:00:00", which SQLite does not match against "Y-m-d" lookups,
     * so updateOrCreate() would miss the existing row and hit the unique index.
     */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Illuminate\Support\Carbon::parse($value)->toDateString();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
