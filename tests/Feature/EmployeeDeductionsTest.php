<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeDeductionsTest extends TestCase
{
    public function test_recurring_and_one_time_deductions_are_selected_for_the_payroll_period(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Database-per-tenant integration test requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'Deductions '.Str::random(6)]);

        try {
            tenancy()->initialize($tenant);
            $employee = Employee::create([
                'name' => 'Deduction Employee',
                'national_id' => 'D-'.Str::uuid(),
                'hire_date' => '2026-01-01',
                'salary' => '1000.000',
                'status' => 'active',
            ]);

            EmployeeDeduction::create([
                'employee_id' => $employee->id,
                'name' => 'Parking',
                'amount' => '10.000',
                'recurring' => true,
                'starts_on' => '2026-01-01',
                'active' => true,
            ]);
            EmployeeDeduction::create([
                'employee_id' => $employee->id,
                'name' => 'One-time recovery',
                'amount' => '25.000',
                'recurring' => false,
                'starts_on' => '2026-09-10',
                'one_time_date' => '2026-09-10',
                'active' => true,
            ]);
            EmployeeDeduction::create([
                'employee_id' => $employee->id,
                'name' => 'Future deduction',
                'amount' => '30.000',
                'recurring' => false,
                'starts_on' => '2026-10-10',
                'one_time_date' => '2026-10-10',
                'active' => true,
            ]);

            $rows = EmployeeDeduction::query()
                ->where('employee_id', $employee->id)
                ->applicableTo(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'))
                ->orderBy('amount')
                ->get();

            $this->assertCount(2, $rows);
            $this->assertSame(['10.000', '25.000'], $rows->pluck('amount')->all());
            $this->assertFalse($rows->first()->reduces_taxable_income);
            $this->assertFalse($rows->first()->reduces_ssc_base);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }
}
