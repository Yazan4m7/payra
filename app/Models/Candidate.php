<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class Candidate extends Model { use SoftDeletes; protected $fillable=['name','email','phone','national_id','source','notes']; public function applications():HasMany{return $this->hasMany(CandidateApplication::class);} }
