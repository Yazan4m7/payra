<?php
namespace App\Services;
use App\Models\NotificationDispatch; use App\Models\User; use Illuminate\Notifications\Notification;
class NotificationDispatcher { public function once(User $user,string $key,Notification $notification):bool{if(!$user->active)return false;$created=NotificationDispatch::firstOrCreate(['user_id'=>$user->id,'dedupe_key'=>$key],['notification_type'=>$notification::class,'created_at'=>now()]);if(!$created->wasRecentlyCreated)return false;$user->notify($notification);return true;} }
