<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\Payslip;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollAdjustmentService
{
    public function approve(PayrollAdjustment $adjustment, int $approverId): PayrollAdjustment
    {
        if ($adjustment->status !== 'pending') {
            throw new RuntimeException(__('payroll.adjustment_pending_only'));
        }

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return $adjustment->refresh();
    }

    public function void(PayrollAdjustment $adjustment): PayrollAdjustment
    {
        if ($adjustment->status === 'applied') {
            throw new RuntimeException(__('payroll.adjustment_applied_locked'));
        }

        $adjustment->update(['status' => 'void']);
        return $adjustment->refresh();
    }

    public function forPayroll(Employee $employee, int $month, int $year): array
    {
        $rows = PayrollAdjustment::query()
            ->where('employee_id', $employee->id)
            ->where('payment_month', $month)
            ->where('payment_year', $year)
            ->where('status', 'approved')
            ->whereNull('applied_payroll_run_id')
            ->orderBy('id')
            ->get();

        $earningTotal = BigDecimal::zero();
        $deductionTotal = BigDecimal::zero();
        $taxableEarnings = BigDecimal::zero();
        $sscEarnings = BigDecimal::zero();
        $taxableReduction = BigDecimal::zero();
        $sscBaseReduction = BigDecimal::zero();
        $details = [];

        foreach ($rows as $row) {
            $amount = Money::d($row->amount);
            if ($row->kind === 'earning') {
                $earningTotal = $earningTotal->plus($amount);
                if ($row->taxable) $taxableEarnings = $taxableEarnings->plus($amount);
                if ($row->ssc_applicable) $sscEarnings = $sscEarnings->plus($amount);
            } else {
                $deductionTotal = $deductionTotal->plus($amount);
                if ($row->reduces_taxable_income) $taxableReduction = $taxableReduction->plus($amount);
                if ($row->reduces_ssc_base) $sscBaseReduction = $sscBaseReduction->plus($amount);
            }

            $details[] = [
                'adjustment_id' => $row->id,
                'kind' => $row->kind,
                'name' => $row->name,
                'amount_jod' => Money::round($amount),
                'source_period' => $row->source_month && $row->source_year
                    ? sprintf('%02d/%04d', $row->source_month, $row->source_year)
                    : null,
                'reason' => $row->reason,
                'taxable' => $row->taxable,
                'ssc_applicable' => $row->ssc_applicable,
                'reduces_taxable_income' => $row->reduces_taxable_income,
                'reduces_ssc_base' => $row->reduces_ssc_base,
            ];
        }

        return [
            'earning_total' => $earningTotal,
            'deduction_total' => $deductionTotal,
            'taxable_earnings' => $taxableEarnings,
            'ssc_earnings' => $sscEarnings,
            'taxable_reduction' => $taxableReduction,
            'ssc_base_reduction' => $sscBaseReduction,
            'details' => $details,
        ];
    }

    public function markApplied(Payslip $payslip, array $details): void
    {
        if ($details === []) return;

        DB::transaction(function () use ($payslip, $details) {
            foreach ($details as $detail) {
                $updated = PayrollAdjustment::query()
                    ->whereKey($detail['adjustment_id'])
                    ->where('status', 'approved')
                    ->whereNull('applied_payroll_run_id')
                    ->update([
                        'status' => 'applied',
                        'applied_payroll_run_id' => $payslip->payroll_run_id,
                        'applied_payslip_id' => $payslip->id,
                        'applied_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException(__('payroll.adjustment_already_consumed'));
                }
            }
        });
    }
}
