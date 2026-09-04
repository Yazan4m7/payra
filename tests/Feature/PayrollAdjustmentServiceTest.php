<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Services\PayrollAdjustmentService;
use App\Support\Money;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollAdjustmentServiceTest extends TestCase
{
    public function test_approved_retro_adjustment_is_paid_in_selected_period_and_consumed_once(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name' => 'Adjustments '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $employee = Employee::create(['name'=>'Adjusted','national_id'=>'ADJ-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);
            $adjustment = PayrollAdjustment::create([
                'employee_id'=>$employee->id,'kind'=>'earning','name'=>'August correction','amount'=>'25.000',
                'payment_month'=>9,'payment_year'=>2026,'source_month'=>8,'source_year'=>2026,
                'taxable'=>true,'ssc_applicable'=>false,'status'=>'approved','reason'=>'Correction',
            ]);
            $service = app(PayrollAdjustmentService::class);
            $calc = $service->forPayroll($employee, 9, 2026);
            $this->assertSame('25.000', Money::round($calc['earning_total']));
            $this->assertCount(1, $calc['details']);

            $run = PayrollRun::create(['month'=>9,'year'=>2026,'status'=>'draft','compliance_setting_id'=>1]);
            $payslip = Payslip::create(['payroll_run_id'=>$run->id,'employee_id'=>$employee->id,'calculation_snapshot'=>[]]);
            $service->markApplied($payslip, $calc['details']);
            $this->assertSame('applied', $adjustment->fresh()->status);
            $this->assertCount(0, $service->forPayroll($employee, 9, 2026)['details']);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
