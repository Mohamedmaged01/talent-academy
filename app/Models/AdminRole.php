<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A named permission set for administrator accounts, optionally limited to the user's own branch. */
class AdminRole extends Model
{
    use HasTranslations;

    protected $fillable = ['name_ar', 'name_en', 'description', 'permissions', 'branch_scoped'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'branch_scoped' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $module, string $action = 'view'): bool
    {
        return Permissions::allows($this->permissions ?? [], $module, $action);
    }
}
