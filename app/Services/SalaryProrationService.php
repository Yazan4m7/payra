<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use RuntimeException;

class SalaryProrationService
{
    public function calculate(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd, array $settings): array
    {
        if (! isset($settings['salary_daily_divisor']) || (string) $settings['salary_daily_divisor'] === '') {
            throw new RuntimeException('Missing compliance setting: salary_daily_divisor');
        }

        $divisor = Money::d((string) $settings['salary_daily_divisor']);
        if ($divisor->isLessThanOrEqualTo(0)) {
            throw new RuntimeException('salary_daily_divisor must be greater than zero.');
        }

        $contractSalary = Money::d($employee->salary);
        $employmentStart = $employee->hire_date->greaterThan($periodStart)
            ? $employee->hire_date->copy()
            : Carbon::instance($periodStart->toDateTime());

        $terminationDate = $employee->terminations()
            ->whereDate('termination_date', '<=', $periodEnd)
            ->orderByDesc('termination_date')
            ->value('termination_date');
        $employmentEnd = $terminationDate && Carbon::parse($terminationDate)->lessThan($periodEnd)
            ? Carbon::parse($terminationDate)
            : Carbon::instance($periodEnd->toDateTime());

        if ($employmentEnd->lt($employmentStart)) {
            return [
                'contract_salary' => Money::round($contractSalary),
                'payable_salary' => '0.000',
                'proration_adjustment' => Money::round($contractSalary),
                'daily_rate' => Money::round($contractSalary->dividedBy($divisor, 12, RoundingMode::HALF_UP)),
                'payable_days' => 0,
                'employment_start' => $employmentStart->toDateString(),
                'employment_end' => $employmentEnd->toDateString(),
                'prorated' => true,
            ];
        }

        $fullPeriod = $employmentStart->isSameDay($periodStart) && $employmentEnd->isSameDay($periodEnd);
        $dailyRate = $contractSalary->dividedBy($divisor, 12, RoundingMode::HALF_UP);
        $days = $employmentStart->diffInDays($employmentEnd) + 1;
        $payable = $fullPeriod ? $contractSalary : Money::min($contractSalary, $dailyRate->multipliedBy((string) $days));
        $adjustment = $contractSalary->minus($payable);

        return [
            'contract_salary' => Money::round($contractSalary),
            'payable_salary' => Money::round($payable),
            'proration_adjustment' => Money::round($adjustment),
            'daily_rate' => Money::round($dailyRate),
            'payable_days' => $days,
            'employment_start' => $employmentStart->toDateString(),
            'employment_end' => $employmentEnd->toDateString(),
            'prorated' => ! $fullPeriod,
        ];
    }
}
