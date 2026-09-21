<?php
namespace Tests\Feature;
use App\Models\Employee; use App\Models\Tenant; use App\Services\SalaryHistoryService; use App\Services\SalaryProrationService; use Carbon\Carbon; use Illuminate\Support\Str; use Tests\TestCase;
class SalaryHistoryTest extends TestCase
{
public function test_mid_month_salary_change_is_split_into_effective_dated_segments():void{if(config('database.default')!=='mysql')$this->markTestSkipped('Requires MySQL.');$tenant=Tenant::create(['name'=>'SalaryHistory '.Str::random(5)]);try{tenancy()->initialize($tenant);$e=Employee::create(['name'=>'Salary Change','national_id'=>'SH-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'300.000','status'=>'active']);$service=app(SalaryHistoryService::class);$service->record($e,'300.000',Carbon::parse('2026-01-01'),'Initial',null);$service->record($e,'600.000',Carbon::parse('2026-09-16'),'Raise',null);$r=app(SalaryProrationService::class)->calculate($e,Carbon::parse('2026-09-01'),Carbon::parse('2026-09-30'),['salary_daily_divisor'=>'30']);$this->assertSame('450.000',$r['payable_salary']);$this->assertCount(2,$r['salary_segments']);$this->assertSame('600.000',$r['contract_salary']);}finally{if(tenancy()->initialized)tenancy()->end();$tenant->delete();}}
}
