<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollRunService
{
    public function __construct(private PayrollEngine $engine, private ComplianceSettingsService $compliance, private LoanService $loans) {}

    public function createRun(int $month, int $year, int $userId): PayrollRun
    {
        return DB::transaction(function () use ($month, $year, $userId) {
            if (PayrollRun::where('month', $month)->where('year', $year)->exists()) throw new RuntimeException(__('hr.error_payroll_exists'));
            $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
            $setting = $this->compliance->forDate($periodEnd);
            return PayrollRun::create(['month' => $month, 'year' => $year, 'status' => 'draft', 'compliance_setting_id' => $setting->id, 'created_by' => $userId]);
        });
    }

    public function process(PayrollRun $run): PayrollRun
    {
        if ($run->status === 'completed') return $run;
        if ($run->status !== 'draft') throw new RuntimeException(__('hr.error_payroll_draft_only'));

        DB::transaction(function () use ($run) {
            $run->update(['status' => 'processing']);
            $run->loadMissing('complianceSetting');
            $periodEnd = Carbon::create($run->year, $run->month, 1)->endOfMonth();
            Employee::query()->whereIn('status', ['active', 'on_leave'])->whereDate('hire_date', '<=', $periodEnd)->orderBy('id')->cursor()->each(function (Employee $employee) use ($run) {
                $calculation = $this->engine->calculate($employee, $run->month, $run->year, $run->complianceSetting);
                $payslip = $run->payslips()->updateOrCreate(['employee_id' => $employee->id], [
                    'gross_salary' => $calculation['gross_salary'], 'overtime_pay' => $calculation['overtime_pay'], 'earnings_total' => $calculation['earnings_total'],
                    'deductions_total' => $calculation['deductions_total'], 'loan_deductions_total' => $calculation['loan_deductions_total'],
                    'ssc_employee' => $calculation['ssc_employee'], 'ssc_employer' => $calculation['ssc_employer'], 'income_tax' => $calculation['income_tax'],
                    'surcharge' => $calculation['surcharge'], 'net_salary' => $calculation['net_salary'], 'calculation_snapshot' => $calculation['snapshot'],
                ]);
                $this->loans->recordRepayments($payslip, $calculation['loan_repayments']);
            });
            $run->update(['status' => 'completed', 'completed_at' => now()]);
        });
        return $run->refresh();
    }
}
