<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\TerminationRecord;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TerminationService
{
    public function __construct(private ComplianceSettingsService $compliance) {}

    public function calculate(Employee $employee, Carbon $terminationDate, ?Carbon $noticeGivenAt, string $unusedLeaveDays): array
    {
        $setting = $this->compliance->forDate($terminationDate);
        $s = $setting->settings;
        $this->compliance->require($s, ['notice_period_days', 'salary_daily_divisor']);

        $salary = Money::d($employee->salary);
        $dailyDivisor = Money::d((string) $s['salary_daily_divisor']);
        if ($dailyDivisor->isLessThanOrEqualTo(0)) {
            throw new RuntimeException('salary_daily_divisor must be greater than zero.');
        }
        $daily = $salary->dividedBy($dailyDivisor, 12, RoundingMode::HALF_UP);

        $required = (int) $s['notice_period_days'];
        $served = $noticeGivenAt ? max(0, min($required, $noticeGivenAt->diffInDays($terminationDate))) : 0;
        $missing = max(0, $required - $served);
        $notice = $daily->multipliedBy((string) $missing);

        $daysWorked = $terminationDate->day;
        $prorated = $daily->multipliedBy((string) $daysWorked);
        $leavePay = $daily->multipliedBy($unusedLeaveDays);
        $final = $prorated->plus($notice)->plus($leavePay);

        return [
            'required_notice_days' => $required,
            'served_notice_days' => $served,
            'notice_pay_in_lieu' => Money::round($notice),
            'prorated_salary' => Money::round($prorated),
            'unused_leave_days' => $unusedLeaveDays,
            'unused_leave_pay' => Money::round($leavePay),
            'final_settlement' => Money::round($final),
            'snapshot' => [
                'setting_version' => $setting->version_label,
                'effective_date' => $setting->effective_date->toDateString(),
                'salary_daily_divisor' => (string) $s['salary_daily_divisor'],
                'daily_rate' => Money::round($daily),
                'days_worked_in_final_month' => $daysWorked,
            ],
        ];
    }

    public function record(Employee $employee, Carbon $date, string $reason, ?Carbon $noticeGivenAt, int $userId): TerminationRecord
    {
        return DB::transaction(function () use ($employee, $date, $reason, $noticeGivenAt, $userId) {
            if ($employee->status === 'terminated' || TerminationRecord::where('employee_id', $employee->id)->exists()) {
                throw new RuntimeException(__('hr.error_termination_exists'));
            }

            $unusedLeaveDays = (string) (LeaveBalance::where('employee_id', $employee->id)->where('year', $date->year)->value('annual_remaining') ?? '0');
            $calculation = $this->calculate($employee, $date, $noticeGivenAt, $unusedLeaveDays);
            $snapshot = $calculation['snapshot'];
            unset($calculation['snapshot']);
            $record = TerminationRecord::create(array_merge($calculation, [
                'employee_id' => $employee->id,
                'termination_date' => $date,
                'reason' => $reason,
                'notice_given_at' => $noticeGivenAt,
                'created_by' => $userId,
                'calculation_snapshot' => $snapshot,
            ]));

            $employee->update(['status' => 'terminated']);
            if ($employee->user) {
                $employee->user->update(['active' => false]);
            }

            return $record;
        });
    }
}
