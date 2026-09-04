<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class TerminationRecord extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id','termination_date','reason','notice_given_at','required_notice_days','served_notice_days','notice_pay_in_lieu','prorated_salary','unused_leave_days','unused_leave_pay','final_settlement','calculation_snapshot','created_by'];
    protected $casts = ['termination_date'=>'date','notice_given_at'=>'date','notice_pay_in_lieu'=>'decimal:3','prorated_salary'=>'decimal:3','unused_leave_days'=>'decimal:2','unused_leave_pay'=>'decimal:3','final_settlement'=>'decimal:3','calculation_snapshot'=>'array'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
