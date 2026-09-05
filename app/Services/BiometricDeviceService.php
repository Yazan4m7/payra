<?php
namespace App\Services;
use App\Models\BiometricDevice; use App\Models\BiometricEmployeeMapping; use App\Models\Employee; use Illuminate\Support\Str;
class BiometricDeviceService { public function create(string $name,string $timezone='Asia/Amman'):array{$token=Str::random(64);$device=BiometricDevice::create(['uuid'=>(string)Str::uuid(),'name'=>$name,'token_hash'=>hash('sha256',$token),'timezone'=>$timezone,'active'=>true]);return ['device'=>$device,'token'=>$token];} public function map(BiometricDevice $device,Employee $employee,string $externalId):BiometricEmployeeMapping{return BiometricEmployeeMapping::updateOrCreate(['biometric_device_id'=>$device->id,'external_employee_id'=>$externalId],['employee_id'=>$employee->id]);} }
