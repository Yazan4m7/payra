<?php

namespace App\Livewire\Employees;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Services\BulkEmployeeActionService;
use Livewire\Component;
use Livewire\WithPagination;

class BulkActions extends Component
{
    use WithPagination;

    public string $search = '';
    public array $selected = [];
    public string $action = 'organization';
    public ?int $branchId = null;
    public ?int $departmentId = null;
    public ?int $costCenterId = null;
    public string $status = 'active';
    public string $salary = '';
    public string $effectiveFrom = '';
    public string $reason = '';

    public function mount(): void
    {
        $this->effectiveFrom = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectVisible(): void
    {
        $this->authorize('employees.manage');
        $ids = $this->employeeQuery()->limit(100)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function apply(BulkEmployeeActionService $service): void
    {
        $this->authorize('employees.manage');
        $this->validate([
            'selected' => ['required', 'array', 'min:1', 'max:1000'],
            'selected.*' => ['integer', 'exists:employees,id'],
            'action' => ['required', 'in:organization,status,salary'],
            'branchId' => ['nullable', 'integer', 'exists:branches,id'],
            'departmentId' => ['nullable', 'integer', 'exists:departments,id'],
            'costCenterId' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'status' => ['required_if:action,status', 'in:active,on_leave,inactive'],
            'salary' => ['required_if:action,salary', 'nullable', 'decimal:0,3', 'min:0'],
            'effectiveFrom' => ['required_if:action,salary', 'nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $count = $service->execute($this->selected, $this->action, [
                'branch_id' => $this->branchId,
                'department_id' => $this->departmentId,
                'cost_center_id' => $this->costCenterId,
                'status' => $this->status,
                'amount' => $this->salary,
                'effective_from' => $this->effectiveFrom,
                'reason' => $this->reason,
            ], auth()->id());
            $this->selected = [];
            session()->flash('success', "{$count} employee(s) updated atomically.");
        } catch (\Throwable $e) {
            $this->addError('bulk', $e->getMessage());
        }
    }

    private function employeeQuery()
    {
        return Employee::query()->when($this->search, function ($query) {
            $term = '%'.$this->search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('national_id', 'like', $term)->orWhere('job_title', 'like', $term));
        })->orderBy('name');
    }

    public function render()
    {
        return view('livewire.employees.bulk-actions', [
            'rows' => $this->employeeQuery()->paginate(50),
            'branches' => Branch::where('active', true)->orderBy('name')->get(),
            'departments' => Department::where('active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
