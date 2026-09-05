<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NotificationDispatch extends Model { public $timestamps=false; protected $fillable=['user_id','dedupe_key','notification_type','created_at']; }
