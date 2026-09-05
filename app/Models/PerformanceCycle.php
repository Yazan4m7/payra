<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class PerformanceCycle extends Model { protected $fillable=['name','starts_on','ends_on','status','created_by']; protected $casts=['starts_on'=>'date','ends_on'=>'date']; public function reviews():HasMany{return $this->hasMany(PerformanceReview::class);} }
