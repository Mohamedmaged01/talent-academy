<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureView extends Model
{
    protected $fillable = ['lecture_id', 'student_id', 'views', 'last_viewed_at'];

    protected function casts(): array
    {
        return ['last_viewed_at' => 'datetime'];
    }

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public static function record(Lecture $lecture, Student $student): void
    {
        $view = static::firstOrNew(['lecture_id' => $lecture->id, 'student_id' => $student->id]);
        $view->views = $view->exists ? $view->views + 1 : 1;
        $view->last_viewed_at = now();
        $view->save();
    }
}
