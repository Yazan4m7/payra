<?php

namespace App\Services;

use App\Models\ComplianceSetting;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ComplianceSettingsService
{
    public function forDate(CarbonInterface|string $date): ComplianceSetting
    {
        $setting = ComplianceSetting::query()->effectiveOn($date)->first();
        if (! $setting) {
            throw new RuntimeException(__('hr.error_no_compliance'));
        }

        return $setting;
    }

    public function require(array $settings, array $keys): void
    {
        $missing = array_values(array_filter(
            $keys,
            fn ($key) => ! array_key_exists($key, $settings) || $settings[$key] === '' || $settings[$key] === null
        ));
        if ($missing) {
            throw new RuntimeException(__('hr.error_missing_compliance', ['keys' => implode(', ', $missing)]));
        }
    }

    public function validatePayload(array $s): array
    {
        $validated = Validator::make($s, [
            'ssc_employee_percent' => 'required|decimal:0,6|min:0|max:100',
            'ssc_employer_percent' => 'required|decimal:0,6|min:0|max:100',
            'ssc_enrollment_cutoff_date' => 'required|date',
            'ssc_ceiling_pre_cutoff_jod' => 'required|decimal:0,3|min:0',
            'ssc_ceiling_post_cutoff_jod' => 'required|decimal:0,3|min:0',

            'income_tax_brackets' => 'required|array|min:1',
            'income_tax_brackets.*.up_to_jod' => 'nullable|decimal:0,3|min:0',
            'income_tax_brackets.*.rate_percent' => 'required|decimal:0,6|min:0|max:100',
            'personal_exemption_annual_jod' => 'required|decimal:0,3|min:0',
            'high_earner_surcharge_threshold_annual_jod' => 'required|decimal:0,3|min:0',
            'high_earner_surcharge_percent' => 'required|decimal:0,6|min:0|max:100',

            'annual_leave_tiers' => 'required|array|min:1',
            'annual_leave_tiers.*.min_years' => 'required|integer|min:0',
            'annual_leave_tiers.*.days' => 'required|decimal:0,2|min:0',
            'sick_leave_paid_days' => 'required|decimal:0,2|min:0',
            'sick_leave_hospital_extra_days' => 'required|decimal:0,2|min:0',

            'overtime_multiplier_standard' => 'required|decimal:0,6|gt:0',
            'overtime_multiplier_rest_holiday' => 'required|decimal:0,6|gt:0',
            'overtime_daily_cap_hours' => 'nullable|decimal:0,2|gt:0',
            'overtime_weekly_cap_hours' => 'nullable|decimal:0,2|gt:0',
            'overtime_monthly_cap_hours' => 'nullable|decimal:0,2|gt:0',
            'overtime_warning_percent' => 'required|decimal:0,2|min:0|max:100',

            'notice_period_days' => 'required|integer|min:0',
            'monthly_hours_divisor' => 'required|decimal:0,3|gt:0',
            'salary_daily_divisor' => 'required|decimal:0,3|gt:0',
            'weekly_rest_days' => 'required|array|min:1|max:7',
            'weekly_rest_days.*' => 'integer|min:1|max:7|distinct',
            'leave_count_public_holidays' => 'required|boolean',
            'leave_count_weekly_rest_days' => 'required|boolean',

            'filing_deadlines' => 'present|array',
            'filing_deadlines.*.name' => 'required|string|max:255',
            'filing_deadlines.*.rule' => 'nullable|string|max:500',
            'filing_deadlines.*.due_day' => 'nullable|integer|min:1|max:31',
        ])->validate();

        $previous = null;
        foreach ($validated['income_tax_brackets'] as $i => $bracket) {
            $upTo = $bracket['up_to_jod'] ?? null;
            if ($upTo === null && $i !== array_key_last($validated['income_tax_brackets'])) {
                throw ValidationException::withMessages(['income_tax_brackets' => 'Only the final tax bracket may be open-ended.']);
            }
            if ($upTo !== null && $previous !== null && BigDecimal::of((string) $upTo)->isLessThanOrEqualTo(BigDecimal::of((string) $previous))) {
                throw ValidationException::withMessages(['income_tax_brackets' => 'Tax bracket ceilings must be strictly increasing.']);
            }
            if ($upTo !== null) {
                $previous = $upTo;
            }
        }
        if (($validated['income_tax_brackets'][array_key_last($validated['income_tax_brackets'])]['up_to_jod'] ?? null) !== null) {
            throw ValidationException::withMessages(['income_tax_brackets' => 'The final tax bracket must be open-ended (up_to_jod = null).']);
        }

        if ((int) $validated['annual_leave_tiers'][0]['min_years'] !== 0) {
            throw ValidationException::withMessages(['annual_leave_tiers' => 'The first annual leave tier must start at 0 years.']);
        }
        $years = -1;
        foreach ($validated['annual_leave_tiers'] as $tier) {
            if ((int) $tier['min_years'] <= $years) {
                throw ValidationException::withMessages(['annual_leave_tiers' => 'Leave tiers must be ordered by strictly increasing min_years.']);
            }
            $years = (int) $tier['min_years'];
        }

        return $validated;
    }
}
