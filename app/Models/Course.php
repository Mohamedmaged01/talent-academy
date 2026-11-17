<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, HasTranslations;

    public const PATHWAYS = ['academic', 'training'];

    protected $fillable = [
        'code', 'pathway', 'category_ar', 'category_en', 'title_ar', 'title_en',
        'description_ar', 'description_en', 'level_ar', 'level_en', 'duration_hours',
        'duration_weeks', 'price', 'image_path', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return asset($this->pathway === 'academic' ? 'images/pathway_academic.jpg' : 'images/pathway_training.jpg');
        }

        return Str::startsWith($this->image_path, ['images/', 'http']) ? asset($this->image_path) : Storage::url($this->image_path);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
