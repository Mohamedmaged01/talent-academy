<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'teacher', 'student', 'parent'];

    protected $fillable = [
        'branch_id', 'name', 'email', 'phone', 'role', 'avatar_path',
        'locale', 'is_active', 'last_login_at', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Mirror the DB default so freshly created (not yet reloaded) users are active. */
    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function studentRecord(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function taughtBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'teacher_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'student' => route('student.dashboard'),
            'parent' => route('parent.dashboard'),
            default => route('home'),
        };
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::url($this->avatar_path);
        }

        // Initials avatar rendered locally, so user names are never sent to a third-party service.
        $palette = ['#0874B9', '#064A7A', '#123B5D', '#0E7490', '#7C3AED', '#B45309'];
        $color = $palette[crc32($this->email ?? $this->name) % count($palette)];
        $letter = htmlspecialchars($this->initials(), ENT_QUOTES | ENT_XML1);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="'.$color.'"/>'
            .'<text x="32" y="42" font-family="Alexandria,Outfit,sans-serif" font-size="28" font-weight="700" fill="#fff" text-anchor="middle">'.$letter.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function initials(): string
    {
        // Skip honorifics like "أ." / "د." / "م." so the avatar shows the real first letter.
        $parts = preg_split('/\s+/u', trim((string) $this->name)) ?: [''];
        $first = collect($parts)->first(fn ($p) => ! preg_match('/\.$/u', $p)) ?? $parts[0];

        return mb_substr($first, 0, 1);
    }
}
