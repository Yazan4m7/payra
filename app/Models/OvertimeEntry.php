<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class OvertimeEntry extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id','date','hours','rate_type','status','approver_id','approved_at','notes'];
    protected $casts = ['date'=>'date','hours'=>'decimal:2','approved_at'=>'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class,'approver_id'); }
}
