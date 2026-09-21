<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use RuntimeException;
class AuditLog extends Model { public $timestamps=false; protected $fillable=['user_id','event','auditable_type','auditable_id','before','after','ip_address','user_agent','request_id','created_at']; protected $casts=['before'=>'array','after'=>'array','created_at'=>'datetime']; public function user():BelongsTo{return $this->belongsTo(User::class);} protected static function booted():void{static::updating(fn()=>throw new RuntimeException('Audit records are immutable.'));static::deleting(fn()=>throw new RuntimeException('Audit records are immutable.'));} }
