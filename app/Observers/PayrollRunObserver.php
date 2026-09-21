<?php
namespace App\Observers;
use App\Models\PayrollRun; use App\Notifications\PayrollApprovedNotification; use App\Services\NotificationDispatcher; use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
class PayrollRunObserver implements ShouldHandleEventsAfterCommit { public function updated(PayrollRun $run):void{if(!$run->wasChanged('status')||$run->status!=='approved')return;$run->load('payslips.employee.user');$dispatcher=app(NotificationDispatcher::class);foreach($run->payslips as $p){$user=$p->employee->user;if($user)$dispatcher->once($user,"payroll-approved:{$run->id}",PayrollApprovedNotification::fromRun($run,(string)$p->net_salary));}} }
