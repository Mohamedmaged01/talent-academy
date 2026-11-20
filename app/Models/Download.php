<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Downloads-center file: forms, guides, schedules and brochures, each aimed at an audience. */
class Download extends Model
{
    use HasTranslations;

    public const CATEGORIES = ['forms', 'guides', 'schedules', 'brochures', 'other'];

    /** "public" is visible to everyone including visitors; "members" to any signed-in user. */
    public const AUDIENCES = ['public', 'members', 'students', 'teachers', 'parents'];

    protected $fillable = [
        'title_ar', 'title_en', 'description_ar', 'description_en', 'category', 'audience',
        'file_path', 'original_name', 'size_bytes', 'url', 'downloads_count', 'is_published', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Audiences a viewer may open: guests see public files; admins see everything. */
    public static function audiencesFor(?User $user): array
    {
        if (! $user) {
            return ['public'];
        }

        if ($user->isAdmin()) {
            return self::AUDIENCES;
        }

        $role = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents'][$user->role] ?? null;

        return array_values(array_filter(['public', 'members', $role]));
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where('is_published', true)->whereIn('audience', self::audiencesFor($user));
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name ?? $this->file_path ?? '', PATHINFO_EXTENSION)) ?: 'LINK';
    }

    public function humanSize(): ?string
    {
        if (! $this->size_bytes) {
            return null;
        }

        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 1).' MB'
            : max(1, round($this->size_bytes / 1024)).' KB';
    }
}
