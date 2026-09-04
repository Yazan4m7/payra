<?php

namespace App\Services;

use App\Support\Money;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Carbon\Carbon;
use RuntimeException;

final class SscCalculator
{
    public function calculate(BigDecimal $gross, ?CarbonInterface $enrollmentDate, array $settings): array
    {
        foreach ([
            'ssc_employee_percent', 'ssc_employer_percent', 'ssc_enrollment_cutoff_date',
            'ssc_ceiling_pre_cutoff_jod', 'ssc_ceiling_post_cutoff_jod',
        ] as $key) {
            if (! array_key_exists($key, $settings) || $settings[$key] === null || $settings[$key] === '') {
                throw new RuntimeException("Missing SSC setting: {$key}");
            }
        }

        if (! $enrollmentDate) {
            throw new RuntimeException(__('hr.error_ssc_enrollment_required'));
        }

        $cutoff = Carbon::parse($settings['ssc_enrollment_cutoff_date']);
        $preCutoff = $enrollmentDate->lt($cutoff);
        $ceiling = Money::d((string) ($preCutoff
            ? $settings['ssc_ceiling_pre_cutoff_jod']
            : $settings['ssc_ceiling_post_cutoff_jod']));
        $base = Money::min($gross, $ceiling);

        return [
            'pre_cutoff' => $preCutoff,
            'ceiling' => $ceiling,
            'base' => $base,
            'employee' => Money::percent($base, (string) $settings['ssc_employee_percent']),
            'employer' => Money::percent($base, (string) $settings['ssc_employer_percent']),
        ];
    }
}
