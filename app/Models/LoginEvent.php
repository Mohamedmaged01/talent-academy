<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Sign-in, sign-out and failed sign-in attempts, for the security log. */
class LoginEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'email', 'event', 'successful', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['successful' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, bool $successful, ?User $user = null, ?string $email = null): void
    {
        $request = request();

        static::create([
            'user_id' => $user?->id,
            'email' => Str::limit(strtolower((string) ($email ?? $user?->email)), 190, ''),
            'event' => $event,
            'successful' => $successful,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 250, ''),
        ]);
    }
}
