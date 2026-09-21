<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class Department extends Model { use SoftDeletes; protected $fillable=['code','name','branch_id','active']; protected $casts=['active'=>'boolean']; public function branch():BelongsTo{return $this->belongsTo(Branch::class);} public function costCenters():HasMany{return $this->hasMany(CostCenter::class);} public function employees():HasMany{return $this->hasMany(Employee::class);} }
