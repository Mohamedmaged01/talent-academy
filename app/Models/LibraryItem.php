<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Digital library entry: a book, training kit, reference or video, stored as a file or a link. */
class LibraryItem extends Model
{
    public const TYPES = ['book', 'training_kit', 'reference', 'video'];

    public const PATHWAYS = ['all', 'academic', 'training'];

    protected $fillable = [
        'title', 'author', 'type', 'pathway', 'course_id', 'description', 'file_path',
        'original_name', 'url', 'pages', 'downloads_count', 'is_published', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function icon(): string
    {
        return match ($this->type) {
            'training_kit' => 'briefcase',
            'reference' => 'library',
            'video' => 'video',
            default => 'book-open',
        };
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
