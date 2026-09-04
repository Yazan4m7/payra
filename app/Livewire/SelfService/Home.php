<?php

namespace App\Livewire\SelfService;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OvertimeEntry;
use App\Services\LeaveService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Home extends Component
{
    public string $type = 'annual';
    public string $start_date = '';
    public string $end_date = '';
    public bool $hospitalized = false;
    public string $reason = '';
    public string $overtime_date = '';
    public string $overtime_hours = '';
    public string $overtime_notes = '';

    public function requestLeave(LeaveService $leaveService): void
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403);
        $data = $this->validate([
            'type' => ['required', 'in:annual,sick'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'hospitalized' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if (Carbon::parse($data['start_date'])->year !== Carbon::parse($data['end_date'])->year) {
            throw ValidationException::withMessages(['end_date' => __('hr.leave_same_year')]);
        }

        try {
            $leaveService->createRequest(
                $employee,
                $data['type'],
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
                (bool) $data['hospitalized'],
                $data['reason'] ?: null,
            );
            $this->reset('start_date', 'end_date', 'hospitalized', 'reason');
            session()->flash('success', __('hr.leave_requested'));
        } catch (\RuntimeException $e) {
            $this->addError('leave', $e->getMessage());
        }
    }

    public function requestOvertime(OvertimeService $service): void
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403);
        $data = $this->validate([
            'overtime_date' => ['required', 'date'],
            'overtime_hours' => ['required', 'decimal:0,2', 'gt:0'],
            'overtime_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $date = Carbon::parse($data['overtime_date']);
            OvertimeEntry::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'hours' => $data['overtime_hours'],
                'rate_type' => $service->rateTypeForDate($date),
                'status' => 'pending',
                'notes' => $data['overtime_notes'],
            ]);
            $this->reset('overtime_date', 'overtime_hours', 'overtime_notes');
            session()->flash('success', __('hr.overtime_requested'));
        } catch (\RuntimeException $e) {
            $this->addError('overtime', $e->getMessage());
        }
    }

    public function render(LeaveService $leaveService, OvertimeService $overtimeService)
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', now()->year)->first();
        if (! $balance) {
            try {
                $balance = $leaveService->ensureBalance($employee, (int) now()->year);
            } catch (\RuntimeException) {
                $balance = null;
            }
        }

        try {
            $capStatus = $overtimeService->capStatus($employee, now());
        } catch (\RuntimeException) {
            $capStatus = ['hours' => '0', 'cap' => null, 'percent' => null, 'warning' => false];
        }

        return view('livewire.self-service.home', [
            'employee' => $employee,
            'balance' => $balance,
            'requests' => LeaveRequest::where('employee_id', $employee->id)->latest()->take(10)->get(),
            'overtimeEntries' => OvertimeEntry::where('employee_id', $employee->id)->latest('date')->take(10)->get(),
            'capStatus' => $capStatus,
            'payslips' => $employee->payslips()->with('payrollRun')->latest()->take(12)->get(),
        ]);
    }
}
