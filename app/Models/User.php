<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable
{
    use SoftDeletes;
    protected $fillable = ['name','email','password','role','locale','active'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['password'=>'hashed','active'=>'boolean'];
    public function employee(): HasOne { return $this->hasOne(Employee::class); }
    public function isHr(): bool { return in_array($this->role, ['company_admin','hr'], true); }
}
