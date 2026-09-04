<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Services\LeaveService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('hr:accrue-leave {year?} {--tenant=*}', function () {
    $year = (int) ($this->argument('year') ?: now()->year);
    $requested = collect($this->option('tenant'))->filter()->values();
    $tenants = Tenant::query()
        ->when($requested->isNotEmpty(), fn ($q) => $q->whereIn('id', $requested))
        ->cursor();

    $ok = 0;
    $failed = 0;
    foreach ($tenants as $tenant) {
        try {
            tenancy()->initialize($tenant);
            $service = app(LeaveService::class);
            Employee::whereIn('status', ['active', 'on_leave'])
                ->whereYear('hire_date', '<=', $year)
                ->each(fn (Employee $employee) => $service->ensureBalance($employee, $year));
            $this->line("[{$tenant->id}] leave balances ensured for {$year}.");
            $ok++;
        } catch (Throwable $e) {
            $this->error("[{$tenant->id}] {$e->getMessage()}");
            $failed++;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    $this->info("Completed: {$ok} tenant(s); failed: {$failed}.");
    return $failed > 0 ? 1 : 0;
})->purpose('Create annual leave balances for every tenant using each tenant’s effective compliance settings.');

Schedule::command('hr:accrue-leave')->yearlyOn(1, 1, '00:10')->withoutOverlapping();
