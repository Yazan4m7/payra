<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeEarning;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeEarningsTest extends TestCase
{
    public function test_recurring_and_one_time_earnings_are_selected_for_the_payroll_period(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Database-per-tenant integration test requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'Earnings '.Str::random(6)]);

        try {
            tenancy()->initialize($tenant);
            $employee = Employee::create([
                'name' => 'Earning Employee',
                'national_id' => 'E-'.Str::uuid(),
                'hire_date' => '2026-01-01',
                'salary' => '1000.000',
                'status' => 'active',
            ]);

            EmployeeEarning::create([
                'employee_id' => $employee->id,
                'category' => 'allowance',
                'name' => 'Transport',
                'amount' => '50.000',
                'recurring' => true,
                'starts_on' => '2026-01-01',
                'taxable' => true,
                'ssc_applicable' => true,
                'active' => true,
            ]);
            EmployeeEarning::create([
                'employee_id' => $employee->id,
                'category' => 'bonus',
                'name' => 'September bonus',
                'amount' => '125.000',
                'recurring' => false,
                'starts_on' => '2026-09-15',
                'one_time_date' => '2026-09-15',
                'taxable' => true,
                'ssc_applicable' => false,
                'active' => true,
            ]);
            EmployeeEarning::create([
                'employee_id' => $employee->id,
                'category' => 'commission',
                'name' => 'October commission',
                'amount' => '80.000',
                'recurring' => false,
                'starts_on' => '2026-10-05',
                'one_time_date' => '2026-10-05',
                'taxable' => true,
                'ssc_applicable' => true,
                'active' => true,
            ]);

            $rows = EmployeeEarning::query()
                ->where('employee_id', $employee->id)
                ->applicableTo(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'))
                ->orderBy('amount')
                ->get();

            $this->assertCount(2, $rows);
            $this->assertSame(['50.000', '125.000'], $rows->pluck('amount')->all());
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }
}
