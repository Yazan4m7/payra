<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class BiometricDevice extends Model { use SoftDeletes; protected $fillable=['uuid','name','token_hash','timezone','active','last_seen_at']; protected $hidden=['token_hash']; protected $casts=['active'=>'boolean','last_seen_at'=>'datetime']; public function getRouteKeyName():string{return 'uuid';} }
