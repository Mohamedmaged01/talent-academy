<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A recorded lecture: a video link (YouTube, Vimeo, Google Drive or any URL) shared with one class. */
class Lecture extends Model
{
    protected $fillable = ['batch_id', 'teacher_id', 'title', 'description', 'video_url', 'duration_minutes', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(LectureView::class);
    }

    /** Embeddable player URL for known video hosts, or null when the link must open in a new tab. */
    public function embedUrl(): ?string
    {
        $url = trim($this->video_url);

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0';
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        if (preg_match('~drive\.google\.com/file/d/([A-Za-z0-9_-]+)~', $url, $m)) {
            return 'https://drive.google.com/file/d/'.$m[1].'/preview';
        }

        return null;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
