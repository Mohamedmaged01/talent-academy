<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A teacher's or administrator's note on a student's file, optionally shared with the parents. */
class StudentNote extends Model
{
    protected $fillable = ['student_id', 'author_id', 'batch_id', 'body', 'visible_to_parent'];

    protected function casts(): array
    {
        return ['visible_to_parent' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
