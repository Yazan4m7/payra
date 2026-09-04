<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
class CentralUser extends Authenticatable
{
    use SoftDeletes;
    protected $connection = 'central';
    protected $fillable = ['name','email','password','is_super_admin'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['password'=>'hashed','is_super_admin'=>'boolean'];
}
