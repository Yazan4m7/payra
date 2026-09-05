<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    public function test_employee_can_be_assigned_to_branch_department_and_cost_center(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name' => 'Org '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $branch = Branch::create(['code'=>'AMM','name'=>'Amman']);
            $department = Department::create(['code'=>'OPS','name'=>'Operations','branch_id'=>$branch->id]);
            $cost = CostCenter::create(['code'=>'OPS-01','name'=>'Operations','department_id'=>$department->id]);
            $employee = Employee::create(['name'=>'Worker','national_id'=>'ORG-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'400.000','status'=>'active','branch_id'=>$branch->id,'department_id'=>$department->id,'cost_center_id'=>$cost->id]);

            $this->assertSame('AMM', $employee->branch->code);
            $this->assertSame('OPS', $employee->department->code);
            $this->assertSame('OPS-01', $employee->costCenter->code);

            $branch->delete();
            $employee->refresh();
            $this->assertSame($branch->id, $employee->branch_id, 'Soft deletion preserves the historical foreign key.');
            $this->assertNull($employee->branch, 'Soft-deleted organization nodes are hidden by the relationship scope.');
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
