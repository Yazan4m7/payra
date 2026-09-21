<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\Money;
use Brick\Math\BigDecimal;

class PayrollReportingService
{
    public const MONEY_FIELDS = [
        'gross_salary', 'overtime_pay', 'earnings_total', 'adjustment_earnings_total',
        'deductions_total', 'adjustment_deductions_total', 'loan_deductions_total',
        'unpaid_absence_deduction', 'ssc_employee', 'ssc_employer', 'income_tax',
        'surcharge', 'net_salary',
    ];

    public function ytdForEmployee(Employee $employee, int $year, int $throughMonth = 12): array
    {
        $totals = [];
        foreach (self::MONEY_FIELDS as $field) $totals[$field] = BigDecimal::zero();

        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($query) => $query
                ->where('year', $year)
                ->where('month', '<=', $throughMonth)
                ->whereIn('status', ['approved', 'completed']))
            ->with('payrollRun:id,month,year,status')
            ->get();

        foreach ($payslips as $payslip) {
            foreach (self::MONEY_FIELDS as $field) {
                $totals[$field] = $totals[$field]->plus(Money::d((string) $payslip->{$field}));
            }
        }

        $result = ['year' => $year, 'through_month' => $throughMonth, 'payroll_count' => $payslips->count()];
        foreach ($totals as $field => $value) $result[$field] = Money::round($value);
        return $result;
    }

    public function totalsForRun(PayrollRun $run): array
    {
        $totals = [];
        foreach (self::MONEY_FIELDS as $field) $totals[$field] = BigDecimal::zero();
        $rows = $run->payslips()->get();
        foreach ($rows as $payslip) {
            foreach (self::MONEY_FIELDS as $field) {
                $totals[$field] = $totals[$field]->plus(Money::d((string) $payslip->{$field}));
            }
        }
        $result = ['employees' => $rows->count()];
        foreach ($totals as $field => $value) $result[$field] = Money::round($value);
        return $result;
    }

    public function reconcileRun(PayrollRun $run): array
    {
        $failures = [];
        foreach ($run->payslips()->orderBy('employee_id')->get() as $payslip) {
            $expected = Money::d((string) $payslip->gross_salary)
                ->minus((string) $payslip->unpaid_absence_deduction)
                ->plus((string) $payslip->overtime_pay)
                ->plus((string) $payslip->earnings_total)
                ->plus((string) $payslip->adjustment_earnings_total)
                ->minus((string) $payslip->deductions_total)
                ->minus((string) $payslip->adjustment_deductions_total)
                ->minus((string) $payslip->loan_deductions_total)
                ->minus((string) $payslip->ssc_employee)
                ->minus((string) $payslip->income_tax)
                ->minus((string) $payslip->surcharge);
            $actual = Money::d((string) $payslip->net_salary);
            $difference = $actual->minus($expected);
            if (! $difference->isZero()) {
                $failures[] = [
                    'payslip_id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'expected_net' => Money::round($expected),
                    'actual_net' => Money::round($actual),
                    'difference' => Money::round($difference),
                ];
            }
        }

        return ['ok' => $failures === [], 'failures' => $failures, 'checked' => $run->payslips()->count()];
    }
}
