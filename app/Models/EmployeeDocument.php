<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeDocument extends Model { use SoftDeletes; protected $fillable=['employee_id','category','original_name','storage_path','mime_type','size_bytes','sha256','expires_at','uploaded_by','notes']; protected $casts=['expires_at'=>'date']; public function employee():BelongsTo{return $this->belongsTo(Employee::class);} public function uploader():BelongsTo{return $this->belongsTo(User::class,'uploaded_by');} }
