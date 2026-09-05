<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PerformanceReview extends Model { protected $fillable=['performance_cycle_id','employee_id','reviewer_id','status','overall_rating','goals_rating','competency_rating','strengths','improvements','goals','employee_comment','submitted_at','acknowledged_at']; protected $casts=['overall_rating'=>'decimal:2','goals_rating'=>'decimal:2','competency_rating'=>'decimal:2','submitted_at'=>'datetime','acknowledged_at'=>'datetime']; public function cycle():BelongsTo{return $this->belongsTo(PerformanceCycle::class,'performance_cycle_id');} public function employee():BelongsTo{return $this->belongsTo(Employee::class);} }
