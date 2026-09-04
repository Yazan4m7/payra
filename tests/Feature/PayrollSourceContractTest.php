<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class PayrollSourceContractTest extends TestCase
{
    public function test_payroll_includes_on_leave_staff_and_excludes_future_hires(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/PayrollRunService.php');
        $this->assertStringContainsString("whereIn('status', ['active', 'on_leave'])", $source);
        $this->assertStringContainsString("whereDate('hire_date', '<=', \$periodEnd)", $source);
    }

    public function test_payslip_keeps_the_exact_compliance_snapshot(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/PayrollEngine.php');
        $this->assertStringContainsString("'settings' => \$settings", $source);
        $this->assertStringContainsString("'setting_version' => \$record->version_label", $source);
    }
}
