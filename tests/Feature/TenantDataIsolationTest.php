<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantDataIsolationTest extends TestCase
{
    public function test_employee_rows_are_physically_isolated_between_tenant_databases(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Database-per-tenant integration test requires MySQL with CREATE DATABASE privilege.');
        }

        $a = Tenant::create(['name' => 'Isolation A '.Str::random(6)]);
        $b = Tenant::create(['name' => 'Isolation B '.Str::random(6)]);

        try {
            tenancy()->initialize($a);
            Employee::create([
                'name' => 'Tenant A Employee', 'national_id' => 'A-'.Str::uuid(), 'hire_date' => '2026-01-01',
                'salary' => '100.000', 'status' => 'active',
            ]);
            $this->assertSame(['Tenant A Employee'], Employee::pluck('name')->all());
            tenancy()->end();

            tenancy()->initialize($b);
            Employee::create([
                'name' => 'Tenant B Employee', 'national_id' => 'B-'.Str::uuid(), 'hire_date' => '2026-01-01',
                'salary' => '200.000', 'status' => 'active',
            ]);
            $this->assertSame(['Tenant B Employee'], Employee::pluck('name')->all());
            tenancy()->end();

            tenancy()->initialize($a);
            $this->assertSame(['Tenant A Employee'], Employee::pluck('name')->all());
            $this->assertDatabaseMissing('employees', ['name' => 'Tenant B Employee']);
            tenancy()->end();
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $a->delete();
            $b->delete();
        }
    }
}
