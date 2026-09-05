<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Carbon\Carbon;

class PayrollComparisonService
{
    public function previousRegular(PayrollRun $run): ?PayrollRun
    {
        $previous = Carbon::create($run->year, $run->month, 1)->subMonth();

        return PayrollRun::where('run_type', 'regular')
            ->where('year', $previous->year)
            ->where('month', $previous->month)
            ->whereIn('status', ['approved', 'completed'])
            ->orderByDesc('sequence')
            ->first();
    }

    public function compare(PayrollRun $current, ?PayrollRun $previous = null): array
    {
        $previous ??= $this->previousRegular($current);
        $current->loadMissing('payslips.employee');
        $previous?->loadMissing('payslips.employee');

        $currentRows = $current->payslips->keyBy('employee_id');
        $previousRows = $previous?->payslips?->keyBy('employee_id') ?? collect();
        $ids = $currentRows->keys()->merge($previousRows->keys())->unique()->sort()->values();
        $rows = [];

        foreach ($ids as $id) {
            $currentSlip = $currentRows->get($id);
            $previousSlip = $previousRows->get($id);
            $employee = $currentSlip?->employee ?: $previousSlip?->employee;
            $rows[] = [
                'employee_id' => $id,
                'employee_name' => $employee?->name,
                'state' => $currentSlip && $previousSlip ? 'continuing' : ($currentSlip ? 'entered' : 'missing'),
                'current_net' => $currentSlip ? (string) $currentSlip->net_salary : '0.000',
                'previous_net' => $previousSlip ? (string) $previousSlip->net_salary : '0.000',
                'net_delta' => Money::round(Money::d($currentSlip ? (string) $currentSlip->net_salary : '0')->minus($previousSlip ? (string) $previousSlip->net_salary : '0')),
                'current_gross' => $currentSlip ? (string) $currentSlip->gross_salary : '0.000',
                'previous_gross' => $previousSlip ? (string) $previousSlip->gross_salary : '0.000',
                'gross_delta' => Money::round(Money::d($currentSlip ? (string) $currentSlip->gross_salary : '0')->minus($previousSlip ? (string) $previousSlip->gross_salary : '0')),
                'current_ssc' => $currentSlip ? (string) $currentSlip->ssc_employee : '0.000',
                'previous_ssc' => $previousSlip ? (string) $previousSlip->ssc_employee : '0.000',
                'current_tax' => $currentSlip ? (string) $currentSlip->income_tax : '0.000',
                'previous_tax' => $previousSlip ? (string) $previousSlip->income_tax : '0.000',
            ];
        }

        $totals = [];
        foreach (['gross_salary', 'net_salary', 'ssc_employee', 'ssc_employer', 'income_tax', 'surcharge'] as $field) {
            $currentTotal = BigDecimal::zero();
            foreach ($currentRows as $row) {
                $currentTotal = $currentTotal->plus(Money::d((string) $row->{$field}));
            }
            $previousTotal = BigDecimal::zero();
            foreach ($previousRows as $row) {
                $previousTotal = $previousTotal->plus(Money::d((string) $row->{$field}));
            }
            $totals[$field] = [
                'current' => Money::round($currentTotal),
                'previous' => Money::round($previousTotal),
                'delta' => Money::round($currentTotal->minus($previousTotal)),
            ];
        }

        return [
            'current' => ['id' => $current->id, 'month' => $current->month, 'year' => $current->year],
            'previous' => $previous ? ['id' => $previous->id, 'month' => $previous->month, 'year' => $previous->year] : null,
            'totals' => $totals,
            'employees' => $rows,
        ];
    }
}
