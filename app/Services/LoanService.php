<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
use App\Models\Payslip;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;

class LoanService
{
    public function dueForPeriod(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        $loans = EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $periodStart);
            })
            ->withSum('repayments', 'amount')
            ->orderBy('id')
            ->get();

        $total = BigDecimal::zero();
        $details = [];

        foreach ($loans as $loan) {
            $paid = Money::d((string) ($loan->repayments_sum_amount ?? '0'));
            $remaining = Money::d($loan->principal)->minus($paid);
            if ($remaining->isLessThanOrEqualTo(0)) {
                continue;
            }

            $installment = Money::d($loan->installment_amount);
            $due = Money::min($installment, $remaining);
            $total = $total->plus($due);
            $details[] = [
                'loan_id' => $loan->id,
                'type' => $loan->type,
                'name' => $loan->name,
                'amount_jod' => Money::round($due),
                'remaining_before_jod' => Money::round($remaining),
                'principal_jod' => Money::round(Money::d($loan->principal)),
            ];
        }

        return ['total' => $total, 'details' => $details];
    }

    public function recordRepayments(Payslip $payslip, array $details): void
    {
        foreach ($details as $detail) {
            LoanRepayment::updateOrCreate(
                [
                    'employee_loan_id' => $detail['loan_id'],
                    'payroll_run_id' => $payslip->payroll_run_id,
                ],
                [
                    'payslip_id' => $payslip->id,
                    'amount' => $detail['amount_jod'],
                ]
            );
        }
    }
}
