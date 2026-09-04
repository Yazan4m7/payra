<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Shift extends Model { use SoftDeletes; protected $fillable=['code','name','start_time','end_time','break_minutes','grace_minutes','working_days','active']; protected $casts=['working_days'=>'array','active'=>'boolean']; }
