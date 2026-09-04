<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
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
                'month' => $month, 'year' => $year, 'status' => 'draft',
                'compliance_setting_id' => $setting->id, 'created_by' => $userId,
            ]);
        });
    }

    public function process(PayrollRun $run): PayrollRun
    {
        if ($run->status === 'completed') return $run;
        if ($run->status !== 'draft') throw new RuntimeException(__('hr.error_payroll_draft_only'));

        DB::transaction(function () use ($run) {
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
                    $payslip = $run->payslips()->updateOrCreate(
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
                    $this->loans->recordRepayments($payslip, $calculation['loan_repayments']);
                    $this->adjustments->markApplied($payslip, $calculation['adjustments']);
                });

            $run->update(['status' => 'completed', 'completed_at' => now()]);
        });

        return $run->refresh();
    }
}
