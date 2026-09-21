<?php
namespace Tests\Feature;
use App\Models\Employee; use App\Models\Tenant; use App\Models\UnpaidAbsence; use App\Services\AbsenceService; use App\Services\SalaryHistoryService; use App\Support\Money; use Carbon\Carbon; use Illuminate\Support\Str; use Tests\TestCase;
class AbsenceServiceTest extends TestCase
{
public function test_approved_absence_uses_salary_effective_on_absence_date():void{if(config('database.default')!=='mysql')$this->markTestSkipped('Requires MySQL.');$tenant=Tenant::create(['name'=>'Absence '.Str::random(5)]);try{tenancy()->initialize($tenant);$e=Employee::create(['name'=>'Absent','national_id'=>'A-'.Str::uuid(),'hire_date'=>'2026-01-01','salary'=>'300.000','status'=>'active']);app(SalaryHistoryService::class)->record($e,'300.000',Carbon::parse('2026-01-01'),null,null);UnpaidAbsence::create(['employee_id'=>$e->id,'type'=>'absence','start_date'=>'2026-09-01','end_date'=>'2026-09-01','days'=>'1','status'=>'approved']);$r=app(AbsenceService::class)->deductionForPeriod($e,Carbon::parse('2026-09-01'),Carbon::parse('2026-09-30'),['salary_daily_divisor'=>'30','weekly_rest_days'=>[5]]);$this->assertSame('10.000',Money::round($r['total']));}finally{if(tenancy()->initialized)tenancy()->end();$tenant->delete();}}
}
