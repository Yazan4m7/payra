<?php
namespace Tests\Feature;
use App\Models\Employee; use App\Models\Tenant; use App\Services\SalaryProrationService; use Carbon\Carbon; use Illuminate\Support\Str; use Tests\TestCase;
class SalaryProrationTest extends TestCase
{
public function test_mid_month_hire_is_prorated_using_daily_divisor():void{if(config('database.default')!=='mysql')$this->markTestSkipped('Requires MySQL.');$tenant=Tenant::create(['name'=>'Proration '.Str::random(5)]);try{tenancy()->initialize($tenant);$e=Employee::create(['name'=>'New Hire','national_id'=>'P-'.Str::uuid(),'hire_date'=>'2026-09-16','salary'=>'300.000','status'=>'active']);$r=app(SalaryProrationService::class)->calculate($e,Carbon::parse('2026-09-01'),Carbon::parse('2026-09-30'),['salary_daily_divisor'=>'30']);$this->assertTrue($r['prorated']);$this->assertSame(15,$r['payable_days']);$this->assertSame('150.000',$r['payable_salary']);$this->assertSame('150.000',$r['proration_adjustment']);}finally{if(tenancy()->initialized)tenancy()->end();$tenant->delete();}}
}
