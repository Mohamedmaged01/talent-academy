<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BatchSchedule extends Model
{
    /** Carbon day-of-week numbering: 0 = Sunday … 6 = Saturday. */
    public const DAYS = [6 => 'Saturday', 0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];

    protected $fillable = ['batch_id', 'day_of_week', 'starts_at', 'ends_at'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function dayName(): string
    {
        return __(self::DAYS[$this->day_of_week] ?? '');
    }

    public function timeRange(): string
    {
        return Carbon::parse($this->starts_at)->format('H:i').' – '.Carbon::parse($this->ends_at)->format('H:i');
    }
}
