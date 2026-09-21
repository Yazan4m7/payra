<?php

namespace Tests\Feature;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PayrollRegisterService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollRegisterTest extends TestCase
{
    public function test_register_contains_totals_and_escapes_spreadsheet_formulas(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name'=>'Register '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $user = User::create(['name'=>'HR','email'=>Str::uuid().'@example.test','password'=>'secret123','role'=>'hr']);
            $setting = ComplianceSetting::create(['version_label'=>'register-test','effective_date'=>'2026-01-01','settings'=>[],'created_by'=>$user->id]);
            $employee = Employee::create(['name'=>'=SUM(A1:A2)','national_id'=>'@FORMULA','hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);
            $run = PayrollRun::create(['month'=>3,'year'=>2026,'status'=>'calculated','compliance_setting_id'=>$setting->id,'created_by'=>$user->id]);
            Payslip::create(['payroll_run_id'=>$run->id,'employee_id'=>$employee->id,'gross_salary'=>'500.000','net_salary'=>'500.000','calculation_snapshot'=>[]]);
            $csv = app(PayrollRegisterService::class)->toCsv($run);
            $this->assertStringContainsString("'=SUM(A1:A2)", $csv);
            $this->assertStringContainsString("'@FORMULA", $csv);
            $this->assertStringContainsString('TOTAL', $csv);
            $this->assertStringContainsString('500.000', $csv);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
