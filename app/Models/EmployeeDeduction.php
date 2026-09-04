<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDeduction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'category',
        'name',
        'amount',
        'recurring',
        'starts_on',
        'ends_on',
        'one_time_date',
        'reduces_taxable_income',
        'reduces_ssc_base',
        'active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'recurring' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'one_time_date' => 'date',
        'reduces_taxable_income' => 'boolean',
        'reduces_ssc_base' => 'boolean',
        'active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeApplicableTo(Builder $query, CarbonInterface $periodStart, CarbonInterface $periodEnd): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $query) use ($periodStart, $periodEnd) {
                $query->where(function (Builder $query) use ($periodStart, $periodEnd) {
                    $query->where('recurring', true)
                        ->whereDate('starts_on', '<=', $periodEnd)
                        ->where(function (Builder $query) use ($periodStart) {
                            $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $periodStart);
                        });
                })->orWhere(function (Builder $query) use ($periodStart, $periodEnd) {
                    $query->where('recurring', false)
                        ->whereBetween('one_time_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                });
            });
    }
}
