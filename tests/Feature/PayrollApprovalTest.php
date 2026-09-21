<?php

namespace Tests\Feature;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PayrollRunService;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PayrollApprovalTest extends TestCase
{
    public function test_calculated_payroll_is_fingerprinted_approved_and_locked(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name' => 'Approval '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $user = User::create(['name'=>'HR','email'=>Str::uuid().'@example.test','password'=>'secret123','role'=>'hr']);
            $setting = ComplianceSetting::create(['version_label'=>'approval-test','effective_date'=>'2026-01-01','settings'=>[],'created_by'=>$user->id]);
            $employee = Employee::create(['name'=>'Employee','national_id'=>'A-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);
            $run = PayrollRun::create(['month'=>9,'year'=>2026,'status'=>'calculated','compliance_setting_id'=>$setting->id,'created_by'=>$user->id]);
            Payslip::create(['payroll_run_id'=>$run->id,'employee_id'=>$employee->id,'gross_salary'=>'500.000','net_salary'=>'450.000','calculation_snapshot'=>[]]);
            $service = app(PayrollRunService::class);
            $run->update(['calculation_hash'=>$service->fingerprint($run)]);

            $approved = $service->approve($run->refresh(), $user->id);
            $this->assertSame('approved', $approved->status);
            $this->assertTrue($approved->isLocked());
            $this->assertSame($user->id, $approved->approved_by);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }

    public function test_changed_payslip_cannot_be_approved(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name' => 'Tamper '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $user = User::create(['name'=>'HR','email'=>Str::uuid().'@example.test','password'=>'secret123','role'=>'hr']);
            $setting = ComplianceSetting::create(['version_label'=>'tamper-test','effective_date'=>'2026-01-01','settings'=>[],'created_by'=>$user->id]);
            $employee = Employee::create(['name'=>'Employee','national_id'=>'T-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);
            $run = PayrollRun::create(['month'=>8,'year'=>2026,'status'=>'calculated','compliance_setting_id'=>$setting->id,'created_by'=>$user->id]);
            $payslip = Payslip::create(['payroll_run_id'=>$run->id,'employee_id'=>$employee->id,'gross_salary'=>'500.000','net_salary'=>'450.000','calculation_snapshot'=>[]]);
            $service = app(PayrollRunService::class);
            $run->update(['calculation_hash'=>$service->fingerprint($run)]);
            $payslip->update(['net_salary'=>'999.000']);

            $this->expectException(RuntimeException::class);
            $service->approve($run->refresh(), $user->id);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
