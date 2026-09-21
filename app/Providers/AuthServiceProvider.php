<?php
namespace App\Providers;
use App\Support\PermissionRegistry; use Illuminate\Support\Facades\Gate; use Illuminate\Support\ServiceProvider;
class AuthServiceProvider extends ServiceProvider { public function boot():void{Gate::define('company-admin',fn($user)=>$user->role==='company_admin');Gate::define('manage-hr',fn($user)=>$user->hasPermission('hr.access'));foreach(PermissionRegistry::ALL as $permission)Gate::define($permission,fn($user)=>$user->hasPermission($permission));} }
