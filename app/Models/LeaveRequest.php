<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class LeaveRequest extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id','type','start_date','end_date','days','hospitalized','status','approver_id','approved_at','reason'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','days'=>'decimal:2','hospitalized'=>'boolean','approved_at'=>'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class,'approver_id'); }
}
