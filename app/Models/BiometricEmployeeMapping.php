<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BiometricEmployeeMapping extends Model { protected $fillable=['biometric_device_id','employee_id','external_employee_id']; public function device():BelongsTo{return $this->belongsTo(BiometricDevice::class,'biometric_device_id');} public function employee():BelongsTo{return $this->belongsTo(Employee::class);} }
