<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** An uploaded image or document that can be placed on the public website. */
class MediaItem extends Model
{
    protected $fillable = ['title', 'path', 'mime', 'size_bytes', 'alt', 'uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Str::startsWith($this->path, ['images/', 'http']) ? asset($this->path) : Storage::url($this->path);
    }

    public function isImage(): bool
    {
        return Str::startsWith((string) $this->mime, 'image/');
    }

    public function humanSize(): string
    {
        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 1).' MB'
            : max(1, round($this->size_bytes / 1024)).' KB';
    }
}
