<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const CATEGORIES = ['technical', 'academic', 'financial', 'account', 'other'];

    public const PRIORITIES = ['normal', 'high', 'urgent'];

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    protected $fillable = ['number', 'user_id', 'subject', 'category', 'priority', 'status', 'body', 'last_reply_at', 'closed_at'];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public static function nextNumber(): string
    {
        $prefix = 'TCK-'.now()->format('Y').'-';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at')->orderBy('id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
