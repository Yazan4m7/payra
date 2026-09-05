<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class EmployeeImportService
{
    public function __construct(private SalaryHistoryService $salaries) {}

    public function import(array $rows, string $fileName, int $userId): ImportBatch
    {
        $batch=ImportBatch::create(['kind'=>'employees','file_name'=>$fileName,'status'=>'processing','total_rows'=>count($rows),'created_by'=>$userId]);
        $success=0; $errors=[];
        foreach($rows as $index=>$row){try{DB::transaction(function()use($row,$userId){$this->importRow($row,$userId);});$success++;}catch(Throwable $e){$errors[]=['row'=>$index+2,'message'=>$e->getMessage()];}}
        $failed=count($errors);$batch->update(['status'=>$failed===0?'completed':($success===0?'failed':'partial'),'succeeded_rows'=>$success,'failed_rows'=>$failed,'errors'=>$errors?:null,'completed_at'=>now()]);return $batch->refresh();
    }

    private function importRow(array $row, int $userId): void
    {
        foreach(['name','national_id','hire_date','salary'] as $required) if(blank($row[$required]??null)) throw new RuntimeException("Missing required column: {$required}");
        $status=$row['status']??'active'; if(!in_array($status,['active','on_leave','terminated','inactive'],true)) throw new RuntimeException('Invalid employee status.');
        $hire=Carbon::parse($row['hire_date']); $salary=Money::round(Money::d((string)$row['salary']));
        $branch=$this->resolve(Branch::class,$row['branch_code']??null,'branch');$department=$this->resolve(Department::class,$row['department_code']??null,'department');$cost=$this->resolve(CostCenter::class,$row['cost_center_code']??null,'cost center');
        $employee=Employee::where('national_id',trim((string)$row['national_id']))->first();
        $attributes=['name'=>trim((string)$row['name']),'hire_date'=>$hire->toDateString(),'job_title'=>$this->nullable($row['job_title']??null),'ssc_number'=>$this->nullable($row['ssc_number']??null),'ssc_enrollment_date'=>$this->dateOrNull($row['ssc_enrollment_date']??null),'bank_iban'=>$this->nullable($row['bank_iban']??null),'status'=>$status,'branch_id'=>$branch?->id,'department_id'=>$department?->id,'cost_center_id'=>$cost?->id];
        if(!$employee){$employee=Employee::create(array_merge($attributes,['national_id'=>trim((string)$row['national_id']),'salary'=>$salary]));$this->salaries->record($employee,$salary,$hire,'Initial salary from import',$userId);return;}
        $employee->update($attributes);
        if((string)$employee->salary!==$salary){$effective=$row['salary_effective_from']??null;if(blank($effective))throw new RuntimeException('salary_effective_from is required when changing an existing employee salary.');$this->salaries->record($employee,$salary,Carbon::parse($effective),'Salary change from import',$userId);}
    }
    private function resolve(string $model, mixed $code, string $label){if(blank($code))return null;$row=$model::where('code',trim((string)$code))->first();if(!$row)throw new RuntimeException("Unknown {$label} code: {$code}");return $row;}
    private function nullable(mixed $v):?string{$v=trim((string)$v);return $v===''?null:$v;}
    private function dateOrNull(mixed $v):?string{return blank($v)?null:Carbon::parse($v)->toDateString();}
}
