<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use RuntimeException;
use Illuminate\Database\Eloquent\SoftDeletes;
class ComplianceSetting extends Model
{
    use SoftDeletes;
    protected $fillable = ['version_label','effective_date','settings','created_by'];
    protected $casts = ['effective_date'=>'date','settings'=>'array'];
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function payrollRuns(): HasMany { return $this->hasMany(PayrollRun::class); }
    protected static function booted(): void { static::updating(fn() => throw new RuntimeException('Compliance setting versions are immutable; create a new effective-dated version.')); static::deleting(function(self $model){ if($model->payrollRuns()->exists()) throw new RuntimeException('A compliance setting used by payroll cannot be deleted.'); }); }
    public function scopeEffectiveOn(Builder $query, $date): Builder { return $query->whereDate('effective_date','<=',$date)->orderByDesc('effective_date')->orderByDesc('id'); }
}
