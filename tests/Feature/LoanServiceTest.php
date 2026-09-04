<?php
namespace Tests\Feature;
use App\Models\Employee; use App\Models\EmployeeLoan; use App\Models\Tenant; use App\Services\LoanService; use Carbon\Carbon; use Illuminate\Support\Str; use Tests\TestCase;
class LoanServiceTest extends TestCase
{
public function test_installment_is_capped_at_remaining_principal(): void
{
if(config('database.default')!=='mysql') $this->markTestSkipped('Database-per-tenant integration test requires MySQL.');
$tenant=Tenant::create(['name'=>'Loans '.Str::random(6)]);
try{tenancy()->initialize($tenant);$employee=Employee::create(['name'=>'Loan Employee','national_id'=>'L-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'1000.000','status'=>'active']);EmployeeLoan::create(['employee_id'=>$employee->id,'type'=>'advance','name'=>'Advance','principal'=>'100.000','installment_amount'=>'150.000','starts_on'=>'2026-09-01','status'=>'active']);$due=app(LoanService::class)->dueForPeriod($employee,Carbon::parse('2026-09-01'),Carbon::parse('2026-09-30'));$this->assertSame('100.000',\App\Support\Money::round($due['total']));$this->assertCount(1,$due['details']);}finally{if(tenancy()->initialized)tenancy()->end();$tenant->delete();}
}
}
