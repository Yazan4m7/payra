<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'national_id', 'ssc_number', 'ssc_enrollment_date', 'hire_date',
        'job_title', 'salary', 'bank_iban', 'status',
    ];

    protected $casts = [
        'ssc_enrollment_date' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:3',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function earnings(): HasMany { return $this->hasMany(EmployeeEarning::class); }
    public function deductions(): HasMany { return $this->hasMany(EmployeeDeduction::class); }
    public function loans(): HasMany { return $this->hasMany(EmployeeLoan::class); }
    public function leaveBalances(): HasMany { return $this->hasMany(LeaveBalance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function overtimeEntries(): HasMany { return $this->hasMany(OvertimeEntry::class); }
    public function onboardingTasks(): HasMany { return $this->hasMany(OnboardingTask::class); }
    public function terminations(): HasMany { return $this->hasMany(TerminationRecord::class); }

    public function isSscRegistered(): bool
    {
        return filled($this->ssc_number) && filled($this->ssc_enrollment_date);
    }
}
