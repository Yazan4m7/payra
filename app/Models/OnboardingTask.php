<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class OnboardingTask extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id','task_key','title_ar','title_en','due_date','completed_at','completed_by'];
    protected $casts = ['due_date'=>'date','completed_at'=>'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
