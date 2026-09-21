<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalaryHistoryService
{
    public function record(Employee $employee, string $amount, CarbonInterface $effectiveFrom, ?string $reason, ?int $userId): EmployeeSalaryHistory
    {
        if (Money::d($amount)->isLessThan(0)) {
            throw new RuntimeException('Salary cannot be negative.');
        }
        if ($effectiveFrom->lt($employee->hire_date)) {
            throw new RuntimeException(__('payroll.error_salary_before_hire'));
        }

        return DB::transaction(function () use ($employee, $amount, $effectiveFrom, $reason, $userId) {
            $history = EmployeeSalaryHistory::updateOrCreate(
                ['employee_id' => $employee->id, 'effective_from' => $effectiveFrom->toDateString()],
                ['amount' => Money::round(Money::d($amount)), 'reason' => $reason, 'created_by' => $userId]
            );

            $current = $this->salaryAt($employee, now());
            $employee->update(['salary' => Money::round($current)]);

            return $history;
        });
    }

    public function salaryAt(Employee $employee, CarbonInterface $date): BigDecimal
    {
        $amount = EmployeeSalaryHistory::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('amount');

        return Money::d((string) ($amount ?? $employee->salary));
    }

    public function changesBetween(Employee $employee, CarbonInterface $start, CarbonInterface $end)
    {
        return EmployeeSalaryHistory::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '>', $start)
            ->whereDate('effective_from', '<=', $end)
            ->orderBy('effective_from')
            ->get();
    }
}
