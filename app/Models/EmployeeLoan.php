<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'type', 'name', 'principal', 'installment_amount', 'starts_on', 'ends_on', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'principal' => 'decimal:3',
        'installment_amount' => 'decimal:3',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function remainingBalance(): string
    {
        $paid = Money::d((string) ($this->repayments_sum_amount ?? $this->repayments()->sum('amount')));
        $remaining = Money::d($this->principal)->minus($paid);

        return Money::round($remaining->isLessThan(0) ? Money::d('0') : $remaining);
    }
}
