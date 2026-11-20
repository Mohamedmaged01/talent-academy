<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** One staff member's pay for one month. Paying it records a salaries expense, which posts to the ledger. */
class PayrollRecord extends Model
{
    use Concerns\ScopedToBranch;

    protected $fillable = [
        'user_id', 'branch_id', 'period', 'basic_salary', 'allowances', 'bonus', 'deductions',
        'status', 'method', 'paid_at', 'paid_by', 'expense_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deductions' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function net(): float
    {
        return round((float) $this->basic_salary + (float) $this->allowances + (float) $this->bonus - (float) $this->deductions, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function periodLabel(): string
    {
        return Carbon::createFromFormat('Y-m-d', $this->period.'-01')->translatedFormat('F Y');
    }
}
