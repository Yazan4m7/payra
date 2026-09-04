<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportBatch extends Model { protected $fillable=['kind','file_name','status','total_rows','succeeded_rows','failed_rows','errors','created_by','completed_at']; protected $casts=['errors'=>'array','completed_at'=>'datetime']; public function creator():BelongsTo{return $this->belongsTo(User::class,'created_by');} }
