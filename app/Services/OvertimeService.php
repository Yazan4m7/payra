<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeEntry;
use App\Models\PublicHoliday;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OvertimeService
{
    public function __construct(private ComplianceSettingsService $compliance) {}

    public function rateTypeForDate(Carbon $date): string
    {
        $s = $this->compliance->forDate($date)->settings;
        $this->compliance->require($s, ['weekly_rest_days']);
        $isHoliday = PublicHoliday::whereDate('date', $date)->exists();
        $isRest = in_array($date->dayOfWeekIso, array_map('intval', $s['weekly_rest_days']), true);

        return ($isHoliday || $isRest) ? 'rest_holiday' : 'standard';
    }

    public function approve(OvertimeEntry $entry, int $approverId): OvertimeEntry
    {
        if ($entry->status === 'approved') {
            return $entry;
        }

        return DB::transaction(function () use ($entry, $approverId) {
            $entry->refresh();
            if ($entry->status !== 'pending') {
                throw new RuntimeException('Only pending overtime can be approved.');
            }
            $this->assertCap($entry);
            $entry->update(['status' => 'approved', 'approver_id' => $approverId, 'approved_at' => now()]);

            return $entry->refresh();
        });
    }

    public function capStatus(Employee $employee, Carbon $date): array
    {
        $s = $this->compliance->forDate($date)->settings;
        $this->compliance->require($s, ['overtime_warning_percent']);
        $hours = BigDecimal::of((string) OvertimeEntry::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->sum('hours'));

        $cap = ($s['overtime_monthly_cap_hours'] ?? null) !== null
            ? BigDecimal::of((string) $s['overtime_monthly_cap_hours'])
            : null;
        $warning = false;
        $percent = null;

        if ($cap && $cap->isGreaterThan(0)) {
            $percent = $hours->multipliedBy('100')->dividedBy($cap, 4, \Brick\Math\RoundingMode::HALF_UP);
            $warning = $percent->isGreaterThanOrEqualTo(BigDecimal::of((string) $s['overtime_warning_percent']));
        }

        return [
            'hours' => (string) $hours,
            'cap' => $cap ? (string) $cap : null,
            'percent' => $percent ? (string) $percent : null,
            'warning' => $warning,
        ];
    }

    private function assertCap(OvertimeEntry $entry): void
    {
        $s = $this->compliance->forDate($entry->date)->settings;
        $entryHours = BigDecimal::of((string) $entry->hours);

        foreach ([
            'daily' => 'overtime_daily_cap_hours',
            'weekly' => 'overtime_weekly_cap_hours',
            'monthly' => 'overtime_monthly_cap_hours',
        ] as $period => $key) {
            if (! array_key_exists($key, $s) || $s[$key] === null || $s[$key] === '') {
                continue;
            }

            $query = OvertimeEntry::query()
                ->where('employee_id', $entry->employee_id)
                ->where('status', 'approved');

            if ($period === 'daily') {
                $query->whereDate('date', $entry->date);
            } elseif ($period === 'weekly') {
                $query->whereBetween('date', [$entry->date->copy()->startOfWeek(), $entry->date->copy()->endOfWeek()]);
            } else {
                $query->whereYear('date', $entry->date->year)->whereMonth('date', $entry->date->month);
            }

            $existing = BigDecimal::of((string) $query->sum('hours'));
            $cap = BigDecimal::of((string) $s[$key]);
            if ($existing->plus($entryHours)->isGreaterThan($cap)) {
                throw new RuntimeException(__('hr.error_overtime_cap', ['period' => __('hr.period_'.$period)]));
            }
        }
    }
}
