<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    public const METHODS = ['cash', 'vodafone_cash', 'instapay', 'bank_transfer', 'card'];

    protected $fillable = [
        'receipt_number', 'invoice_id', 'student_id', 'amount', 'method',
        'reference', 'paid_at', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public static function nextReceiptNumber(): string
    {
        $prefix = 'RCP-'.now()->format('Y').'-';
        $last = static::where('receipt_number', 'like', $prefix.'%')->orderByDesc('receipt_number')->value('receipt_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }
}
