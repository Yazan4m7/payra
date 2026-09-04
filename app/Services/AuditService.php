<?php
namespace App\Services;
use App\Models\AuditLog; use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Str;
class AuditService
{
private const REDACT=['password','remember_token','api_token','token','secret','private_key'];
public function record(string $event,Model $model,?array $before=null,?array $after=null):void{if($model instanceof AuditLog)return;if(!function_exists('tenancy')||!tenancy()->initialized)return;$request=app()->bound('request')?request():null;$requestId=null;if($request){$requestId=$request->attributes->get('audit_request_id');if(!$requestId){$requestId=(string)Str::uuid();$request->attributes->set('audit_request_id',$requestId);}}AuditLog::create(['user_id'=>auth()->id(),'event'=>$event,'auditable_type'=>$model::class,'auditable_id'=>$model->getKey(),'before'=>$this->sanitize($before),'after'=>$this->sanitize($after),'ip_address'=>$request?->ip(),'user_agent'=>mb_substr((string)($request?->userAgent()),0,1000)?:null,'request_id'=>$requestId,'created_at'=>now()]);}
private function sanitize(?array $values):?array{if($values===null)return null;foreach($values as $key=>$value){$lower=strtolower((string)$key);foreach(self::REDACT as $blocked)if(str_contains($lower,$blocked)){$values[$key]='[REDACTED]';continue 2;}}return $values;}
}
