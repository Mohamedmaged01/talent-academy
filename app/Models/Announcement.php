<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const AUDIENCES = ['all', 'students', 'teachers', 'parents', 'batch'];

    protected $fillable = ['title', 'body', 'audience', 'batch_id', 'author_id', 'is_pinned'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Announcements a user should see: global ones for their role, plus
     * batch-scoped ones for batches they teach / study in / their children study in.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roleAudience = match ($user->role) {
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents',
            default => null,
        };

        $batchIds = match ($user->role) {
            'teacher' => $user->taughtBatches()->pluck('id'),
            'student' => $user->studentRecord?->batches()->pluck('batches.id') ?? collect(),
            'parent' => Enrollment::whereIn('student_id', $user->children()->pluck('students.id'))->pluck('batch_id'),
            default => collect(),
        };

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($roleAudience, $batchIds) {
            $q->where('audience', 'all')
                ->when($roleAudience, fn ($q) => $q->orWhere('audience', $roleAudience))
                ->orWhere(fn ($q) => $q->where('audience', 'batch')->whereIn('batch_id', $batchIds));
        });
    }
}
