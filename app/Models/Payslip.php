<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Payslip extends Model
{
    use SoftDeletes;
    protected $fillable = ['payroll_run_id','employee_id','gross_salary','overtime_pay','ssc_employee','ssc_employer','income_tax','surcharge','net_salary','calculation_snapshot'];
    protected $casts = [
        'gross_salary'=>'decimal:3','overtime_pay'=>'decimal:3','ssc_employee'=>'decimal:3','ssc_employer'=>'decimal:3',
        'income_tax'=>'decimal:3','surcharge'=>'decimal:3','net_salary'=>'decimal:3','calculation_snapshot'=>'array'
    ];
    public function payrollRun(): BelongsTo { return $this->belongsTo(PayrollRun::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
