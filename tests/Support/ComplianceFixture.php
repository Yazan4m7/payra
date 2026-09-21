<?php

namespace Tests\Support;

use App\Models\ComplianceSetting;
use Illuminate\Support\Str;

final class ComplianceFixture
{
    public static function settings(): array
    {
        return [
            'ssc_employee_percent' => '7.500000',
            'ssc_employer_percent' => '14.250000',
            'ssc_enrollment_cutoff_date' => '2010-01-01',
            'ssc_ceiling_pre_cutoff_jod' => '5000.000',
            'ssc_ceiling_post_cutoff_jod' => '5000.000',
            'income_tax_brackets' => [
                ['up_to_jod' => null, 'rate_percent' => '0.000000'],
            ],
            'personal_exemption_annual_jod' => '0.000',
            'high_earner_surcharge_threshold_annual_jod' => '999999.000',
            'high_earner_surcharge_percent' => '0.000000',
            'annual_leave_tiers' => [
                ['min_years' => 0, 'days' => '14.00'],
            ],
            'sick_leave_paid_days' => '14.00',
            'sick_leave_hospital_extra_days' => '14.00',
            'overtime_multiplier_standard' => '1.250000',
            'overtime_multiplier_rest_holiday' => '1.500000',
            'overtime_daily_cap_hours' => null,
            'overtime_weekly_cap_hours' => null,
            'overtime_monthly_cap_hours' => null,
            'overtime_warning_percent' => '80.00',
            'notice_period_days' => 30,
            'monthly_hours_divisor' => '240.000',
            'salary_daily_divisor' => '30.000',
            'weekly_rest_days' => [5],
            'leave_count_public_holidays' => false,
            'leave_count_weekly_rest_days' => false,
            'filing_deadlines' => [],
        ];
    }

    public static function create(?int $userId = null): ComplianceSetting
    {
        return ComplianceSetting::create([
            'version_label' => 'test-'.Str::lower(Str::random(10)),
            'effective_date' => '2026-01-01',
            'settings' => self::settings(),
            'created_by' => $userId,
        ]);
    }
}
