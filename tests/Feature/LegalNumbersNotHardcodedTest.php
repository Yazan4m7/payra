<?php
namespace Tests\Feature;
use PHPUnit\Framework\TestCase;
class LegalNumbersNotHardcodedTest extends TestCase
{
    public function test_payroll_engine_reads_legal_values_from_settings(): void
    {
        $source=file_get_contents(__DIR__.'/../../app/Services/PayrollEngine.php');
        foreach(['ssc_employee_percent','ssc_employer_percent','ssc_ceiling_pre_cutoff_jod','ssc_ceiling_post_cutoff_jod','personal_exemption_annual_jod','high_earner_surcharge_percent','overtime_multiplier_standard','overtime_multiplier_rest_holiday'] as $key) $this->assertStringContainsString($key,$source);
    }
}
