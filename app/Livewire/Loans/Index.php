<?php

namespace App\Livewire\Loans;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingId = null;
    public array $form = [];

    public function mount(): void { $this->resetForm(); }

    public function startEdit(int $id): void
    {
        $this->authorize('manage-hr');
        $loan = EmployeeLoan::findOrFail($id); $this->editingId = $loan->id;
        $this->form = ['employee_id' => $loan->employee_id, 'type' => $loan->type, 'name' => $loan->name, 'principal' => $loan->principal, 'installment_amount' => $loan->installment_amount, 'starts_on' => $loan->starts_on?->toDateString() ?? '', 'ends_on' => $loan->ends_on?->toDateString() ?? '', 'status' => $loan->status, 'notes' => $loan->notes ?? ''];
    }

    public function cancelEdit(): void { $this->editingId = null; $this->resetForm(); $this->resetValidation(); }

    public function save(): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'form.employee_id' => ['required', 'exists:employees,id'], 'form.type' => ['required', 'in:loan,advance'], 'form.name' => ['required', 'string', 'max:255'],
            'form.principal' => ['required', 'decimal:0,3', 'min:0.001'], 'form.installment_amount' => ['required', 'decimal:0,3', 'min:0.001'],
            'form.starts_on' => ['required', 'date'], 'form.ends_on' => ['nullable', 'date', 'after_or_equal:form.starts_on'], 'form.status' => ['required', 'in:active,paused,cancelled'], 'form.notes' => ['nullable', 'string', 'max:2000'],
        ])['form'];
        $payload = $data + ['ends_on' => $data['ends_on'] ?: null, 'notes' => $data['notes'] ?: null, 'created_by' => auth()->id()];
        if ($this->editingId) EmployeeLoan::findOrFail($this->editingId)->update($payload); else EmployeeLoan::create($payload);
        $this->editingId = null; $this->resetForm(); session()->flash('success', __('hr.saved'));
    }

    private function resetForm(): void
    {
        $this->form = ['employee_id' => '', 'type' => 'loan', 'name' => '', 'principal' => '', 'installment_amount' => '', 'starts_on' => now()->startOfMonth()->toDateString(), 'ends_on' => '', 'status' => 'active', 'notes' => ''];
    }

    public function render()
    {
        return view('livewire.loans.index', ['employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(['id', 'name']), 'rows' => EmployeeLoan::with('employee')->withSum('repayments', 'amount')->latest('id')->get()]);
    }
}
