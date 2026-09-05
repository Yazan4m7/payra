<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\Tenant;
use App\Services\RecruitingService;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class RecruitingServiceTest extends TestCase
{
    public function test_pipeline_enforces_transitions_and_hiring_is_atomic(): void
    {
        if (config('database.default') !== 'mysql') $this->markTestSkipped('Requires MySQL.');
        $tenant=Tenant::create(['name'=>'Recruit '.Str::random(5)]);
        try{tenancy()->initialize($tenant);$opening=JobOpening::create(['title'=>'Payroll Specialist','openings_count'=>1,'status'=>'open','opened_on'=>'2026-09-01']);$candidate=Candidate::create(['name'=>'Candidate','national_id'=>'REC-100']);$service=app(RecruitingService::class);$application=$service->apply($candidate,$opening->id);
            try{$service->transition($application,'offer',null);$this->fail('Illegal stage jump should fail.');}catch(RuntimeException){$this->assertSame('applied',$application->fresh()->stage);}
            $application=$service->transition($application,'screening',null);$application=$service->transition($application,'interview',null);$application=$service->transition($application,'offer',null);$employee=$service->hire($application,'850.000','2026-09-15',null);
            $this->assertSame('REC-100',$employee->national_id);$this->assertSame('hired',$application->fresh()->stage);$this->assertSame('closed',$opening->fresh()->status);$this->assertDatabaseHas('employee_salary_histories',['employee_id'=>$employee->id,'amount'=>'850.000']);
        }finally{if(tenancy()->initialized)tenancy()->end();$tenant->delete();}
    }
}
