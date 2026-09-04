<?php

namespace App\Livewire\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Requests extends Component
{
    public ?int $employee_id = null;
    public string $type = 'annual';
    public string $start_date = '';
    public string $end_date = '';
    public bool $hospitalized = false;
    public string $reason = '';

    public function create(LeaveService $service): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
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
            $service->createRequest(
                Employee::findOrFail($data['employee_id']),
                $data['type'],
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
                (bool) $data['hospitalized'],
                $data['reason'] ?: null,
            );
            $this->reset('employee_id', 'start_date', 'end_date', 'hospitalized', 'reason');
            session()->flash('success', __('hr.leave_requested'));
        } catch (\RuntimeException $e) {
            $this->addError('leave', $e->getMessage());
        }
    }

    public function approve(int $id, LeaveService $service): void
    {
        $this->authorize('manage-hr');
        try {
            $service->approve(LeaveRequest::with('employee')->findOrFail($id), auth()->id());
            session()->flash('success', __('hr.approved'));
        } catch (\RuntimeException $e) {
            $this->addError('leave', $e->getMessage());
        }
    }

    public function reject(int $id): void
    {
        $this->authorize('manage-hr');
        LeaveRequest::whereKey($id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'approver_id' => auth()->id(),
        ]);
    }

    public function render()
    {
        return view('livewire.leave.requests', [
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(['id', 'name']),
            'requests' => LeaveRequest::with(['employee.leaveBalances', 'approver'])->latest()->take(50)->get(),
        ]);
    }
}
