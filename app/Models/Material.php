<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    public const TYPES = ['file', 'link', 'video'];

    protected $fillable = ['batch_id', 'uploaded_by', 'title', 'type', 'file_path', 'original_name', 'url', 'description'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
