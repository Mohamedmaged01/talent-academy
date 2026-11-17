<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUSES = ['unpaid', 'partial', 'paid', 'overdue', 'cancelled'];

    protected $fillable = [
        'number', 'student_id', 'branch_id', 'batch_id', 'description', 'amount',
        'discount', 'paid_amount', 'issued_at', 'due_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'date',
            'due_date' => 'date',
        ];
    }

    public static function nextNumber(): string
    {
        $prefix = 'INV-'.now()->format('Y').'-';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function total(): float
    {
        return round((float) $this->amount - (float) $this->discount, 2);
    }

    public function balance(): float
    {
        return max(0, round($this->total() - (float) $this->paid_amount, 2));
    }

    /** Recalculate paid_amount and status from the payments table. */
    public function recalculate(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $this->paid_amount = round((float) $this->payments()->sum('amount'), 2);

        $this->status = match (true) {
            $this->paid_amount >= $this->total() => 'paid',
            $this->paid_amount > 0 => 'partial',
            $this->due_date->isPast() => 'overdue',
            default => 'unpaid',
        };

        $this->save();
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
    }
}
