<?php
namespace App\Services;
use App\Models\EmployeeContract; use App\Models\User; use App\Notifications\ContractExpiringNotification; use Carbon\CarbonInterface;
class ContractReminderService { public function __construct(private NotificationDispatcher $dispatcher){} public function send(CarbonInterface $asOf,int $days=30):int{$contracts=EmployeeContract::with('employee')->expiringBetween($asOf->toDateString(),$asOf->copy()->addDays($days)->toDateString())->get();$users=User::whereIn('role',['company_admin','hr'])->where('active',true)->get();$sent=0;foreach($contracts as $c)foreach($users as $u)if($this->dispatcher->once($u,"contract-expiring:{$c->id}:{$c->end_date->toDateString()}",ContractExpiringNotification::fromContract($c)))$sent++;return $sent;} }
