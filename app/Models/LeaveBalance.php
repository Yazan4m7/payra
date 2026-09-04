<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class LeaveBalance extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id','year','compliance_setting_id','annual_entitlement','annual_used','annual_remaining','sick_entitlement','sick_used','sick_remaining','hospital_extra_entitlement','hospital_extra_used','hospital_extra_remaining'];
    protected $casts = ['annual_entitlement'=>'decimal:2','annual_used'=>'decimal:2','annual_remaining'=>'decimal:2','sick_entitlement'=>'decimal:2','sick_used'=>'decimal:2','sick_remaining'=>'decimal:2','hospital_extra_entitlement'=>'decimal:2','hospital_extra_used'=>'decimal:2','hospital_extra_remaining'=>'decimal:2'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function complianceSetting(): BelongsTo { return $this->belongsTo(ComplianceSetting::class); }
}
