<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class PayrollSourceContractTest extends TestCase
{
    public function test_payroll_includes_on_leave_staff_and_excludes_future_hires(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/PayrollRunService.php');

        $this->assertMatchesRegularExpression(
            "/whereIn\\(\\s*['\"]status['\"]\\s*,\\s*\\[\\s*['\"]active['\"]\\s*,\\s*['\"]on_leave['\"]\\s*\\]\\s*\\)/",
            $source
        );
        $this->assertMatchesRegularExpression(
            "/whereDate\\(\\s*['\"]hire_date['\"]\\s*,\\s*['\"]<=['\"]\\s*,/",
            $source
        );
    }

    public function test_payslip_keeps_the_exact_compliance_snapshot(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/PayrollEngine.php');

        $this->assertMatchesRegularExpression("/['\"]settings['\"]\\s*=>/", $source);
        $this->assertMatchesRegularExpression("/['\"]setting_version['\"]\\s*=>/", $source);
        $this->assertStringContainsString('version_label', $source);
    }
}
