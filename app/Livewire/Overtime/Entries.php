<?php

namespace App\Livewire\Overtime;

use App\Models\Employee;
use App\Models\OvertimeEntry;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Livewire\Component;

class Entries extends Component
{
    public ?int $employee_id = null;
    public string $date = '';
    public string $hours = '';
    public string $notes = '';

    public function create(OvertimeService $service): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'hours' => ['required', 'decimal:0,2', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $date = Carbon::parse($data['date']);
            OvertimeEntry::create([
                'employee_id' => $data['employee_id'],
                'date' => $date,
                'hours' => $data['hours'],
                'rate_type' => $service->rateTypeForDate($date),
                'status' => 'pending',
                'notes' => $data['notes'],
            ]);
            $this->reset('employee_id', 'date', 'hours', 'notes');
            session()->flash('success', __('hr.overtime_requested'));
        } catch (\RuntimeException $e) {
            $this->addError('overtime', $e->getMessage());
        }
    }

    public function approve(int $id, OvertimeService $service): void
    {
        $this->authorize('manage-hr');
        try {
            $service->approve(OvertimeEntry::findOrFail($id), auth()->id());
            session()->flash('success', __('hr.approved'));
        } catch (\RuntimeException $e) {
            $this->addError('overtime', $e->getMessage());
        }
    }

    public function reject(int $id): void
    {
        $this->authorize('manage-hr');
        OvertimeEntry::whereKey($id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'approver_id' => auth()->id(),
        ]);
    }

    public function render(OvertimeService $service)
    {
        $employees = Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(['id', 'name']);
        $capStatuses = [];
        foreach ($employees as $employee) {
            try {
                $capStatuses[$employee->id] = $service->capStatus($employee, now());
            } catch (\RuntimeException) {
                $capStatuses[$employee->id] = ['hours' => '0', 'cap' => null, 'percent' => null, 'warning' => false];
            }
        }

        return view('livewire.overtime.entries', [
            'employees' => $employees,
            'entries' => OvertimeEntry::with(['employee', 'approver'])->latest('date')->take(50)->get(),
            'capStatuses' => $capStatuses,
        ]);
    }
}
