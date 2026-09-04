<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Services\ContractReminderService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('hr:accrue-leave {year?} {--tenant=*}', function () {
    $year=(int)($this->argument('year')?:now()->year);$requested=collect($this->option('tenant'))->filter()->values();$tenants=Tenant::query()->when($requested->isNotEmpty(),fn($q)=>$q->whereIn('id',$requested))->cursor();$ok=0;$failed=0;
    foreach($tenants as $tenant){try{tenancy()->initialize($tenant);$service=app(LeaveService::class);Employee::whereIn('status',['active','on_leave'])->whereYear('hire_date','<=',$year)->each(fn(Employee $employee)=>$service->ensureBalance($employee,$year));$this->line("[{$tenant->id}] leave balances ensured for {$year}.");$ok++;}catch(Throwable $e){$this->error("[{$tenant->id}] {$e->getMessage()}");$failed++;}finally{if(tenancy()->initialized)tenancy()->end();}}
    $this->info("Completed: {$ok} tenant(s); failed: {$failed}.");return $failed>0?1:0;
})->purpose('Create annual leave balances for every tenant using each tenant’s effective compliance settings.');

Artisan::command('hr:notify-contract-expiry {--days=30} {--tenant=*}', function () {
    $days=max(1,(int)$this->option('days'));$requested=collect($this->option('tenant'))->filter()->values();$tenants=Tenant::query()->when($requested->isNotEmpty(),fn($q)=>$q->whereIn('id',$requested))->cursor();$sent=0;$failed=0;
    foreach($tenants as $tenant){try{tenancy()->initialize($tenant);$count=app(ContractReminderService::class)->send(Carbon::today(),$days);$this->line("[{$tenant->id}] {$count} contract reminder(s) dispatched.");$sent+=$count;}catch(Throwable $e){$this->error("[{$tenant->id}] {$e->getMessage()}");$failed++;}finally{if(tenancy()->initialized)tenancy()->end();}}
    $this->info("Dispatched: {$sent}; tenant failures: {$failed}.");return $failed>0?1:0;
})->purpose('Notify HR users about employee contracts nearing expiry.');

Schedule::command('hr:accrue-leave')->yearlyOn(1,1,'00:10')->withoutOverlapping();
Schedule::command('hr:notify-contract-expiry')->dailyAt('08:00')->withoutOverlapping();
