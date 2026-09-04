<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollRun extends Model
{
    use SoftDeletes;
    protected $fillable = ['month','year','status','compliance_setting_id','created_by','completed_at'];
    protected $casts = ['completed_at'=>'datetime'];
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function complianceSetting(): BelongsTo { return $this->belongsTo(ComplianceSetting::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
