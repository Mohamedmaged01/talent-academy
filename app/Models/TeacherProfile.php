<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    use HasTranslations;

    public const TYPES = ['academic_teacher', 'professional_trainer', 'both'];

    protected $fillable = [
        'user_id', 'instructor_type', 'title_ar', 'title_en', 'specialization_ar',
        'specialization_en', 'bio', 'national_id', 'hire_date', 'monthly_salary', 'office_hours',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
