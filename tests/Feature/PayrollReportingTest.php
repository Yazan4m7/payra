<?php

namespace Tests\Feature;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PayrollReportingService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollReportingTest extends TestCase
{
    public function test_ytd_uses_only_approved_runs_and_reconciliation_detects_tampering(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name'=>'Reporting '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $user = User::create(['name'=>'HR','email'=>Str::uuid().'@example.test','password'=>'secret123','role'=>'hr']);
            $setting = ComplianceSetting::create(['version_label'=>'report-test','effective_date'=>'2026-01-01','settings'=>[],'created_by'=>$user->id]);
            $employee = Employee::create(['name'=>'Employee','national_id'=>'R-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);

            $approved = PayrollRun::create(['month'=>1,'year'=>2026,'status'=>'approved','compliance_setting_id'=>$setting->id,'created_by'=>$user->id,'approved_at'=>now(),'locked_at'=>now()]);
            Payslip::create(['payroll_run_id'=>$approved->id,'employee_id'=>$employee->id,'gross_salary'=>'500.000','overtime_pay'=>'10.000','earnings_total'=>'20.000','ssc_employee'=>'30.000','income_tax'=>'5.000','net_salary'=>'495.000','calculation_snapshot'=>[]]);
            $draft = PayrollRun::create(['month'=>2,'year'=>2026,'status'=>'draft','compliance_setting_id'=>$setting->id,'created_by'=>$user->id]);
            Payslip::create(['payroll_run_id'=>$draft->id,'employee_id'=>$employee->id,'gross_salary'=>'900.000','net_salary'=>'900.000','calculation_snapshot'=>[]]);

            $service = app(PayrollReportingService::class);
            $ytd = $service->ytdForEmployee($employee, 2026, 2);
            $this->assertSame(1, $ytd['payroll_count']);
            $this->assertSame('500.000', $ytd['gross_salary']);
            $this->assertSame('495.000', $ytd['net_salary']);
            $this->assertTrue($service->reconcileRun($approved)['ok']);

            $approved->payslips()->first()->update(['net_salary'=>'496.000']);
            $result = $service->reconcileRun($approved);
            $this->assertFalse($result['ok']);
            $this->assertSame('1.000', $result['failures'][0]['difference']);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
