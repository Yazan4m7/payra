<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class Branch extends Model { use SoftDeletes; protected $fillable=['code','name','city','active']; protected $casts=['active'=>'boolean']; public function departments():HasMany{return $this->hasMany(Department::class);} public function employees():HasMany{return $this->hasMany(Employee::class);} }
