<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\OvertimeEntry;
use App\Services\ComplianceDashboardService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(ComplianceDashboardService $service)
    {
        return view('livewire.dashboard', [
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->count(),
            'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
            'pendingOvertime' => OvertimeEntry::where('status', 'pending')->count(),
            'latestPayroll' => PayrollRun::orderByDesc('year')->orderByDesc('month')->first(),
            'compliance' => $service->summary(),
        ]);
    }
}
