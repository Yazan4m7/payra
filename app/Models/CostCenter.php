<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class CostCenter extends Model { use SoftDeletes; protected $fillable=['code','name','department_id','active']; protected $casts=['active'=>'boolean']; public function department():BelongsTo{return $this->belongsTo(Department::class);} public function employees():HasMany{return $this->hasMany(Employee::class);} }
