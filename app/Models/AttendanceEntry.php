<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendanceEntry extends Model { protected $fillable=['employee_id','shift_id','work_date','clock_in','clock_out','worked_minutes','late_minutes','early_leave_minutes','source','status','approved_by','approved_at','notes']; protected $casts=['work_date'=>'date','clock_in'=>'datetime','clock_out'=>'datetime','approved_at'=>'datetime']; public function employee():BelongsTo{return $this->belongsTo(Employee::class);} public function shift():BelongsTo{return $this->belongsTo(Shift::class);} public function approver():BelongsTo{return $this->belongsTo(User::class,'approved_by');} }
