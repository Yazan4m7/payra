<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Tenant;
use App\Services\BulkEmployeeActionService;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class BulkEmployeeActionTest extends TestCase
{
    public function test_bulk_actions_are_atomic_and_salary_changes_use_history(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant = Tenant::create(['name' => 'Bulk '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $branch = Branch::create(['code'=>'AMM','name'=>'Amman','active'=>true]);
            $department = Department::create(['code'=>'OPS','name'=>'Operations','branch_id'=>$branch->id,'active'=>true]);
            $cost = CostCenter::create(['code'=>'OPS1','name'=>'Operations 1','department_id'=>$department->id,'active'=>true]);
            $a = Employee::create(['name'=>'A','national_id'=>'BULK-A','hire_date'=>'2026-01-01','salary'=>'500.000','status'=>'active']);
            $b = Employee::create(['name'=>'B','national_id'=>'BULK-B','hire_date'=>'2026-01-01','salary'=>'600.000','status'=>'active']);
            $service = app(BulkEmployeeActionService::class);

            $this->assertSame(2, $service->execute([$a->id,$b->id], 'organization', ['cost_center_id'=>$cost->id], null));
            $this->assertSame($branch->id, $a->fresh()->branch_id);
            $this->assertSame($department->id, $b->fresh()->department_id);

            $service->execute([$a->id,$b->id], 'salary', ['amount'=>'700.000','effective_from'=>'2026-09-01','reason'=>'Annual review'], null);
            $this->assertDatabaseHas('employee_salary_histories', ['employee_id'=>$a->id,'amount'=>'700.000','effective_from'=>'2026-09-01']);
            $this->assertDatabaseHas('employee_salary_histories', ['employee_id'=>$b->id,'amount'=>'700.000','effective_from'=>'2026-09-01']);

            try {
                $service->execute([$a->id,999999], 'status', ['status'=>'inactive'], null);
                $this->fail('Expected missing employee to abort the batch.');
            } catch (RuntimeException) {
                $this->assertSame('active', $a->fresh()->status);
            }

            $this->expectException(RuntimeException::class);
            $service->execute([$a->id], 'status', ['status'=>'terminated'], null);
        } finally {
            if (tenancy()->initialized) tenancy()->end();
            $tenant->delete();
        }
    }
}
