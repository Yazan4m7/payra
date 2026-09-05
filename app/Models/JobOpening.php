<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class JobOpening extends Model { use SoftDeletes; protected $fillable=['title','department_id','location','employment_type','openings_count','status','opened_on','closed_on','description','created_by']; protected $casts=['opened_on'=>'date','closed_on'=>'date']; public function department():BelongsTo{return $this->belongsTo(Department::class);} public function applications():HasMany{return $this->hasMany(CandidateApplication::class);} }
