<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug', 'category_ar', 'category_en', 'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en',
        'body_ar', 'body_en', 'image_path', 'author_name', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return asset('images/news_programs.jpg');
        }

        return Str::startsWith($this->image_path, ['images/', 'http']) ? asset($this->image_path) : Storage::url($this->image_path);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
