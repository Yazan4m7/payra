<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use RuntimeException;

class SalaryProrationService
{
    public function __construct(private SalaryHistoryService $salaries) {}

    public function calculate(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd, array $settings): array
    {
        $divisor = Money::d((string) ($settings['salary_daily_divisor'] ?? '0'));
        if ($divisor->isLessThanOrEqualTo(0)) throw new RuntimeException('salary_daily_divisor must be greater than zero.');

        $employmentStart = $employee->hire_date->greaterThan($periodStart) ? $employee->hire_date->copy() : Carbon::instance($periodStart->toDateTime());
        $terminationDate = $employee->terminations()->whereDate('termination_date', '<=', $periodEnd)->orderByDesc('termination_date')->value('termination_date');
        $employmentEnd = $terminationDate && Carbon::parse($terminationDate)->lessThan($periodEnd) ? Carbon::parse($terminationDate) : Carbon::instance($periodEnd->toDateTime());
        $contractSalary = $this->salaries->salaryAt($employee, $periodEnd);

        if ($employmentEnd->lt($employmentStart)) {
            return ['contract_salary'=>Money::round($contractSalary),'payable_salary'=>'0.000','proration_adjustment'=>Money::round($contractSalary),'daily_rate'=>'0.000','payable_days'=>0,'employment_start'=>$employmentStart->toDateString(),'employment_end'=>$employmentEnd->toDateString(),'prorated'=>true,'salary_segments'=>[]];
        }

        $changes = $this->salaries->changesBetween($employee, $employmentStart, $employmentEnd);
        $fullPeriod = $employmentStart->isSameDay($periodStart) && $employmentEnd->isSameDay($periodEnd) && $changes->isEmpty();
        $startSalary = $this->salaries->salaryAt($employee, $employmentStart);
        if ($fullPeriod) {
            return ['contract_salary'=>Money::round($contractSalary),'payable_salary'=>Money::round($startSalary),'proration_adjustment'=>Money::round($contractSalary->minus($startSalary)),'daily_rate'=>Money::round($startSalary->dividedBy($divisor,12,RoundingMode::HALF_UP)),'payable_days'=>$periodStart->diffInDays($periodEnd)+1,'employment_start'=>$employmentStart->toDateString(),'employment_end'=>$employmentEnd->toDateString(),'prorated'=>false,'salary_segments'=>[['start'=>$employmentStart->toDateString(),'end'=>$employmentEnd->toDateString(),'monthly_salary'=>Money::round($startSalary),'days'=>$periodStart->diffInDays($periodEnd)+1]]];
        }

        $boundaries = collect([['date'=>$employmentStart->copy(),'salary'=>$startSalary]]);
        foreach ($changes as $change) $boundaries->push(['date'=>$change->effective_from->copy(),'salary'=>Money::d($change->amount)]);
        $payable = BigDecimal::zero(); $segments = []; $daysTotal = 0;
        foreach ($boundaries->values() as $index => $boundary) {
            $segmentStart = $boundary['date'];
            $next = $boundaries->get($index + 1);
            $segmentEnd = $next ? $next['date']->copy()->subDay() : $employmentEnd->copy();
            if ($segmentEnd->gt($employmentEnd)) $segmentEnd = $employmentEnd->copy();
            if ($segmentEnd->lt($segmentStart)) continue;
            $days = $segmentStart->diffInDays($segmentEnd) + 1; $daysTotal += $days;
            $daily = $boundary['salary']->dividedBy($divisor, 12, RoundingMode::HALF_UP); $amount = $daily->multipliedBy((string) $days); $payable = $payable->plus($amount);
            $segments[]=['start'=>$segmentStart->toDateString(),'end'=>$segmentEnd->toDateString(),'monthly_salary'=>Money::round($boundary['salary']),'daily_rate'=>Money::round($daily),'days'=>$days,'amount_jod'=>Money::round($amount)];
        }

        return ['contract_salary'=>Money::round($contractSalary),'payable_salary'=>Money::round($payable),'proration_adjustment'=>Money::round($contractSalary->minus($payable)),'daily_rate'=>Money::round($contractSalary->dividedBy($divisor,12,RoundingMode::HALF_UP)),'payable_days'=>$daysTotal,'employment_start'=>$employmentStart->toDateString(),'employment_end'=>$employmentEnd->toDateString(),'prorated'=>true,'salary_segments'=>$segments];
    }
}
