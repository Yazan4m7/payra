<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecruitingService
{
    private const TRANSITIONS = [
        'applied' => ['screening','rejected'],
        'screening' => ['interview','rejected'],
        'interview' => ['offer','rejected'],
        'offer' => ['hired','rejected'],
        'hired' => [],
        'rejected' => [],
    ];

    public function __construct(private SalaryHistoryService $salaries, private OnboardingService $onboarding) {}

    public function transition(CandidateApplication $application, string $stage, ?int $actorId, ?string $note = null): CandidateApplication
    {
        return DB::transaction(function () use ($application, $stage, $actorId, $note) {
            $application = CandidateApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($stage === 'hired') throw new RuntimeException('Use the hire action so an employee record and salary history are created atomically.');
            if (! in_array($stage, self::TRANSITIONS[$application->stage] ?? [], true)) throw new RuntimeException("Illegal recruiting transition: {$application->stage} → {$stage}.");
            $application->update(['stage'=>$stage,'stage_changed_at'=>now(),'stage_changed_by'=>$actorId,'decision_note'=>$note]);
            return $application->refresh();
        });
    }

    public function hire(CandidateApplication $application, string $salary, string $hireDate, ?int $actorId): Employee
    {
        return DB::transaction(function () use ($application, $salary, $hireDate, $actorId) {
            $application = CandidateApplication::with(['candidate','opening.department'])->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($application->stage !== 'offer') throw new RuntimeException('Only an offered candidate can be hired.');
            $candidate = $application->candidate;
            if (! filled($candidate->national_id)) throw new RuntimeException('Candidate national ID is required before hiring.');
            if (Employee::where('national_id', $candidate->national_id)->exists()) throw new RuntimeException('An employee with this national ID already exists.');
            $date = Carbon::parse($hireDate);
            $department = $application->opening->department;
            $employee = Employee::create([
                'name'=>$candidate->name,
                'national_id'=>$candidate->national_id,
                'hire_date'=>$date->toDateString(),
                'job_title'=>$application->opening->title,
                'branch_id'=>$department?->branch_id,
                'department_id'=>$department?->id,
                'salary'=>$salary,
                'status'=>'active',
            ]);
            $this->salaries->record($employee, $salary, $date, 'Hired from recruiting pipeline', $actorId);
            $this->onboarding->createFor($employee);
            $application->update(['stage'=>'hired','stage_changed_at'=>now(),'stage_changed_by'=>$actorId,'hired_employee_id'=>$employee->id]);
            $hiredCount = CandidateApplication::where('job_opening_id',$application->job_opening_id)->where('stage','hired')->count();
            if ($hiredCount >= $application->opening->openings_count) $application->opening->update(['status'=>'closed','closed_on'=>now()->toDateString()]);
            return $employee;
        });
    }

    public function apply(Candidate $candidate, int $openingId): CandidateApplication
    {
        return CandidateApplication::firstOrCreate(['candidate_id'=>$candidate->id,'job_opening_id'=>$openingId], ['stage'=>'applied','applied_at'=>now()]);
    }
}
