<?php
namespace App\Services;
use App\Models\Employee; use App\Models\EmployeeContract; use Carbon\CarbonInterface; use Illuminate\Support\Facades\DB; use RuntimeException;
class ContractService
{
public function create(Employee $employee,array $data,int $userId):EmployeeContract{if(($data['type']??'indefinite')==='fixed_term'&&empty($data['end_date']))throw new RuntimeException('Fixed-term contracts require an end date.');if(!empty($data['end_date'])&&$data['end_date']<$data['start_date'])throw new RuntimeException('Contract end date cannot precede start date.');return EmployeeContract::create(array_merge($data,['employee_id'=>$employee->id,'created_by'=>$userId,'status'=>$data['status']??'draft']));}
public function activate(EmployeeContract $contract):EmployeeContract{return DB::transaction(function()use($contract){$contract=EmployeeContract::whereKey($contract->id)->lockForUpdate()->firstOrFail();if(in_array($contract->status,['terminated','expired'],true))throw new RuntimeException('Ended contracts cannot be activated.');EmployeeContract::where('employee_id',$contract->employee_id)->where('status','active')->whereKeyNot($contract->id)->update(['status'=>'superseded']);$contract->update(['status'=>'active','signed_at'=>$contract->signed_at?:now()]);return $contract->refresh();});}
public function expireDue(CarbonInterface $asOf):int{return EmployeeContract::query()->where('status','active')->whereNotNull('end_date')->whereDate('end_date','<',$asOf->toDateString())->update(['status'=>'expired']);}
public function expiringWithin(CarbonInterface $asOf,int $days=30){return EmployeeContract::with('employee')->expiringBetween($asOf->toDateString(),$asOf->copy()->addDays($days)->toDateString())->orderBy('end_date')->get();}
}
