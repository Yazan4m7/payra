<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollRunService
{
    public function __construct(
        private PayrollEngine $engine,
        private ComplianceSettingsService $compliance,
        private LoanService $loans,
        private PayrollAdjustmentService $adjustments,
    ) {}

    public function createRun(int $month, int $year, int $userId): PayrollRun
    {
        return DB::transaction(function () use ($month, $year, $userId) {
            if (PayrollRun::where('month', $month)->where('year', $year)->exists()) {
                throw new RuntimeException(__('hr.error_payroll_exists'));
            }
            $setting = $this->compliance->forDate(Carbon::create($year, $month, 1)->endOfMonth());
            return PayrollRun::create([
                'month' => $month,
                'year' => $year,
                'status' => 'draft',
                'compliance_setting_id' => $setting->id,
                'created_by' => $userId,
            ]);
        });
    }

    public function process(PayrollRun $run): PayrollRun
    {
        if ($run->isLocked()) {
            throw new RuntimeException('Approved payroll is locked and cannot be recalculated.');
        }

        DB::transaction(function () use ($run) {
            $run = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($run->status !== 'draft') {
                throw new RuntimeException(__('hr.error_payroll_draft_only'));
            }

            $run->update(['status' => 'processing']);
            $run->loadMissing('complianceSetting');
            $start = Carbon::create($run->year, $run->month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            Employee::query()
                ->whereDate('hire_date', '<=', $end)
                ->where(function (Builder $query) use ($start, $end) {
                    $query->whereIn('status', ['active', 'on_leave'])
                        ->orWhereHas('terminations', fn (Builder $termination) => $termination
                            ->whereBetween('termination_date', [$start->toDateString(), $end->toDateString()]));
                })
                ->orderBy('id')
                ->cursor()
                ->each(function (Employee $employee) use ($run) {
                    $calculation = $this->engine->calculate($employee, $run->month, $run->year, $run->complianceSetting);
                    $run->payslips()->updateOrCreate(
                        ['employee_id' => $employee->id],
                        [
                            'contract_salary' => $calculation['contract_salary'],
                            'gross_salary' => $calculation['gross_salary'],
                            'proration_adjustment' => $calculation['proration_adjustment'],
                            'overtime_pay' => $calculation['overtime_pay'],
                            'earnings_total' => $calculation['earnings_total'],
                            'adjustment_earnings_total' => $calculation['adjustment_earnings_total'],
                            'deductions_total' => $calculation['deductions_total'],
                            'adjustment_deductions_total' => $calculation['adjustment_deductions_total'],
                            'loan_deductions_total' => $calculation['loan_deductions_total'],
                            'unpaid_absence_deduction' => $calculation['unpaid_absence_deduction'],
                            'ssc_employee' => $calculation['ssc_employee'],
                            'ssc_employer' => $calculation['ssc_employer'],
                            'income_tax' => $calculation['income_tax'],
                            'surcharge' => $calculation['surcharge'],
                            'net_salary' => $calculation['net_salary'],
                            'calculation_snapshot' => $calculation['snapshot'],
                        ]
                    );
                });

            $run->update([
                'status' => 'calculated',
                'completed_at' => now(),
                'calculation_hash' => $this->fingerprint($run->refresh()),
            ]);
        });

        return $run->refresh();
    }

    public function approve(PayrollRun $run, int $userId): PayrollRun
    {
        return DB::transaction(function () use ($run, $userId) {
            $run = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($run->status === 'approved' && $run->isLocked()) {
                return $run;
            }
            if ($run->status !== 'calculated') {
                throw new RuntimeException('Only a calculated payroll run can be approved.');
            }

            $actualHash = $this->fingerprint($run);
            if (! $run->calculation_hash || ! hash_equals($run->calculation_hash, $actualHash)) {
                throw new RuntimeException('Payroll calculation changed after calculation. Recalculate before approval.');
            }

            $run->payslips()->orderBy('employee_id')->get()->each(function (Payslip $payslip) {
                $snapshot = $payslip->calculation_snapshot ?? [];
                $this->loans->recordRepayments($payslip, $snapshot['loan_repayment_details'] ?? []);
                $this->adjustments->markApplied($payslip, $snapshot['payroll_adjustments'] ?? []);
            });

            $run->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'locked_at' => now(),
            ]);

            return $run->refresh();
        });
    }

    public function void(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            $run = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($run->isLocked()) {
                throw new RuntimeException('Approved payroll cannot be voided; use an adjustment in a later payroll.');
            }
            if (! in_array($run->status, ['draft', 'calculated'], true)) {
                throw new RuntimeException('Only draft or calculated payroll can be voided.');
            }
            $run->update(['status' => 'void']);
            return $run->refresh();
        });
    }

    public function fingerprint(PayrollRun $run): string
    {
        $rows = $run->payslips()->orderBy('employee_id')->get()->map(fn (Payslip $payslip) => [
            'employee_id' => $payslip->employee_id,
            'contract_salary' => (string) $payslip->contract_salary,
            'gross_salary' => (string) $payslip->gross_salary,
            'proration_adjustment' => (string) $payslip->proration_adjustment,
            'overtime_pay' => (string) $payslip->overtime_pay,
            'earnings_total' => (string) $payslip->earnings_total,
            'adjustment_earnings_total' => (string) $payslip->adjustment_earnings_total,
            'deductions_total' => (string) $payslip->deductions_total,
            'adjustment_deductions_total' => (string) $payslip->adjustment_deductions_total,
            'loan_deductions_total' => (string) $payslip->loan_deductions_total,
            'unpaid_absence_deduction' => (string) $payslip->unpaid_absence_deduction,
            'ssc_employee' => (string) $payslip->ssc_employee,
            'ssc_employer' => (string) $payslip->ssc_employer,
            'income_tax' => (string) $payslip->income_tax,
            'surcharge' => (string) $payslip->surcharge,
            'net_salary' => (string) $payslip->net_salary,
            'snapshot' => $payslip->calculation_snapshot,
        ])->values()->all();

        return hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
