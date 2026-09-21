<?php
namespace App\Models;
use Stancl\Tenancy\Contracts\TenantWithDatabase; use Stancl\Tenancy\Database\Concerns\HasDatabase; use Stancl\Tenancy\Database\Concerns\HasDomains; use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
class Tenant extends BaseTenant implements TenantWithDatabase { use HasDatabase,HasDomains; public static function getCustomColumns():array{return ['id','name','sector','ssc_establishment_number','tax_number','plan','subscription_status','subscription_ends_at'];} protected $casts=['subscription_ends_at'=>'date']; }
