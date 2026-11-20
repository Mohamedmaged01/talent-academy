<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A private conversation between two or more users. Only participants can open it. */
class MessageThread extends Model
{
    protected $fillable = ['subject', 'created_by', 'last_message_at', 'last_message_id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_message_id')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    public function otherParticipants(User $user)
    {
        return $this->participants->reject(fn (User $u) => $u->id === $user->id);
    }

    public function isUnreadFor(User $user): bool
    {
        $lastRead = $this->participants->firstWhere('id', $user->id)?->pivot?->last_read_message_id;

        return $this->last_message_id && (! $lastRead || $this->last_message_id > $lastRead);
    }

    public function markReadFor(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, ['last_read_message_id' => $this->last_message_id]);
    }

    /** Add a message and bump the thread so it sorts to the top for everyone. */
    public function post(User $sender, string $body): Message
    {
        $message = $this->messages()->create(['user_id' => $sender->id, 'body' => $body]);
        $this->forceFill(['last_message_at' => $message->created_at, 'last_message_id' => $message->id])->save();
        $this->markReadFor($sender);

        return $message;
    }
}
