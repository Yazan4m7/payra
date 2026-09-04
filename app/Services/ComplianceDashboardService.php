<?php

namespace App\Services;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\OnboardingTask;
use App\Models\OvertimeEntry;
use App\Models\PublicHoliday;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class ComplianceDashboardService
{
    public function summary(): array
    {
        $now = now();
        $settings = ComplianceSetting::query()->effectiveOn($now)->first();
        $pendingSsc = Employee::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ssc_number')->orWhereNull('ssc_enrollment_date');
            })
            ->whereDate('hire_date', '<=', $now)
            ->orderBy('hire_date')
            ->get();

        $overdueOnboarding = OnboardingTask::query()
            ->with('employee')
            ->whereNull('completed_at')
            ->whereDate('due_date', '<', $now->toDateString())
            ->orderBy('due_date')
            ->get();

        $holidaysMissing = PublicHoliday::where('year', $now->year)->doesntExist();
        $settingsStale = ! $settings || $settings->effective_date->year < $now->year;
        $nearCaps = [];

        if ($settings) {
            $capRaw = $settings->settings['overtime_monthly_cap_hours'] ?? null;
            $warnRaw = $settings->settings['overtime_warning_percent'] ?? null;
            if ($capRaw !== null && $warnRaw !== null) {
                $cap = BigDecimal::of((string) $capRaw);
                $warn = BigDecimal::of((string) $warnRaw);
                if ($cap->isGreaterThan(0)) {
                    $rows = OvertimeEntry::query()
                        ->selectRaw('employee_id, SUM(hours) as total_hours')
                        ->where('status', 'approved')
                        ->whereYear('date', $now->year)
                        ->whereMonth('date', $now->month)
                        ->groupBy('employee_id')
                        ->with('employee')
                        ->get();

                    foreach ($rows as $row) {
                        $percent = BigDecimal::of((string) $row->total_hours)
                            ->multipliedBy('100')
                            ->dividedBy($cap, 4, RoundingMode::HALF_UP);
                        if ($percent->isGreaterThanOrEqualTo($warn)) {
                            $row->setAttribute('cap_percent', (string) $percent);
                            $nearCaps[] = $row;
                        }
                    }
                }
            }
        }

        return [
            'pending_ssc' => $pendingSsc,
            'overdue_onboarding' => $overdueOnboarding,
            'holidays_missing' => $holidaysMissing,
            'settings_stale' => $settingsStale,
            'near_overtime_caps' => $nearCaps,
            'filing_deadlines' => $settings ? ($settings->settings['filing_deadlines'] ?? []) : [],
            'settings_version' => $settings?->version_label,
        ];
    }
}
