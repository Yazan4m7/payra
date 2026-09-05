<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CandidateApplication extends Model { protected $fillable=['candidate_id','job_opening_id','stage','applied_at','stage_changed_at','stage_changed_by','hired_employee_id','decision_note']; protected $casts=['applied_at'=>'datetime','stage_changed_at'=>'datetime']; public function candidate():BelongsTo{return $this->belongsTo(Candidate::class);} public function opening():BelongsTo{return $this->belongsTo(JobOpening::class,'job_opening_id');} public function hiredEmployee():BelongsTo{return $this->belongsTo(Employee::class,'hired_employee_id');} }
