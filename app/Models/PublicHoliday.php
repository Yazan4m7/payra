<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PublicHoliday extends Model
{
    use SoftDeletes;
    protected $fillable = ['date','name_ar','name_en','year'];
    protected $casts = ['date'=>'date','year'=>'integer'];
}
