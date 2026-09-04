<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'kind', 'name', 'amount', 'payment_month', 'payment_year',
        'source_month', 'source_year', 'taxable', 'ssc_applicable',
        'reduces_taxable_income', 'reduces_ssc_base', 'status', 'reason',
        'created_by', 'approved_by', 'approved_at', 'applied_payroll_run_id',
        'applied_payslip_id', 'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'taxable' => 'boolean',
        'ssc_applicable' => 'boolean',
        'reduces_taxable_income' => 'boolean',
        'reduces_ssc_base' => 'boolean',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function appliedPayrollRun(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'applied_payroll_run_id'); }
    public function appliedPayslip(): BelongsTo { return $this->belongsTo(Payslip::class, 'applied_payslip_id'); }
}
