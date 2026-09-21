<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BiometricEvent extends Model { protected $fillable=['biometric_device_id','external_event_id','external_employee_id','event_type','event_at','payload_hash','attendance_entry_id','processed_at','error']; protected $casts=['event_at'=>'datetime','processed_at'=>'datetime']; }
