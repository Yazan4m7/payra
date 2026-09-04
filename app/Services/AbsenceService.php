<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PublicHoliday;
use App\Models\UnpaidAbsence;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbsenceService
{
    public function create(Employee $employee, string $type, Carbon $start, Carbon $end, ?string $reason = null): UnpaidAbsence
    {
        if ($end->lt($start)) {
            throw new RuntimeException('Absence end date cannot be before start date.');
        }

        $overlap = UnpaidAbsence::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
        if ($overlap) {
            throw new RuntimeException(__('payroll.error_absence_overlap'));
        }

        return UnpaidAbsence::create([
            'employee_id' => $employee->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'pending',
            'reason' => $reason,
        ]);
    }

    public function approve(UnpaidAbsence $absence, int $approverId, array $settings): UnpaidAbsence
    {
        return DB::transaction(function () use ($absence, $approverId, $settings) {
            $absence->refresh();
            if ($absence->status !== 'pending') {
                throw new RuntimeException(__('payroll.error_absence_pending_only'));
            }

            $days = $this->chargeableDays($absence->start_date, $absence->end_date, $settings);
            if ($days <= 0) {
                throw new RuntimeException(__('payroll.error_absence_no_days'));
            }

            $absence->update([
                'days' => (string) $days,
                'status' => 'approved',
                'approver_id' => $approverId,
                'approved_at' => now(),
            ]);

            return $absence->refresh();
        });
    }

    public function reject(UnpaidAbsence $absence, int $approverId): UnpaidAbsence
    {
        if ($absence->status !== 'pending') {
            throw new RuntimeException(__('payroll.error_absence_pending_only'));
        }
        $absence->update(['status' => 'rejected', 'approver_id' => $approverId]);
        return $absence->refresh();
    }

    public function deductionForPeriod(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd, BigDecimal $monthlyBaseSalary, array $settings): array
    {
        if (! isset($settings['salary_daily_divisor']) || (string) $settings['salary_daily_divisor'] === '') {
            throw new RuntimeException('Missing compliance setting: salary_daily_divisor');
        }
        $divisor = Money::d((string) $settings['salary_daily_divisor']);
        if ($divisor->isLessThanOrEqualTo(0)) {
            throw new RuntimeException('salary_daily_divisor must be greater than zero.');
        }

        $dailyRate = $monthlyBaseSalary->dividedBy($divisor, 12, RoundingMode::HALF_UP);
        $total = BigDecimal::zero();
        $details = [];

        $rows = UnpaidAbsence::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->orderBy('start_date')
            ->get();

        foreach ($rows as $row) {
            $start = $row->start_date->greaterThan($periodStart) ? $row->start_date : Carbon::instance($periodStart->toDateTime());
            $end = $row->end_date->lessThan($periodEnd) ? $row->end_date : Carbon::instance($periodEnd->toDateTime());
            $days = $this->chargeableDays($start, $end, $settings);
            if ($days <= 0) {
                continue;
            }
            $amount = $dailyRate->multipliedBy((string) $days);
            $total = $total->plus($amount);
            $details[] = [
                'absence_id' => $row->id,
                'type' => $row->type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'daily_rate_jod' => Money::round($dailyRate),
                'amount_jod' => Money::round($amount),
            ];
        }

        return ['total' => $total, 'details' => $details];
    }

    public function chargeableDays(CarbonInterface $start, CarbonInterface $end, array $settings): int
    {
        $restDays = array_map('intval', $settings['weekly_rest_days'] ?? [5]);
        $holidays = PublicHoliday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (in_array($date->dayOfWeekIso, $restDays, true)) {
                continue;
            }
            if (in_array($date->toDateString(), $holidays, true)) {
                continue;
            }
            $days++;
        }

        return $days;
    }
}
