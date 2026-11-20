<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temporary extra permissions for an administrator. Created directly as a
 * delegation ("active"), or as an access request ("pending") that another
 * administrator approves or rejects.
 */
class PermissionDelegation extends Model
{
    public const STATUSES = ['pending', 'active', 'rejected', 'revoked'];

    protected $fillable = [
        'from_user_id', 'to_user_id', 'permissions', 'reason', 'status', 'starts_at', 'ends_at',
        'requested_days', 'reviewed_by', 'reviewed_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Delegations in force right now. */
    public function scopeInForce(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function displayStatus(): string
    {
        if ($this->status === 'active' && $this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        if ($this->status === 'active' && $this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $this->status;
    }
}
