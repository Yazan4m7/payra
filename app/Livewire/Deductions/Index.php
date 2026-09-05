<?php

namespace App\Livewire\Deductions;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function startEdit(int $id): void
    {
        $this->authorize('manage-hr');
        $deduction = EmployeeDeduction::findOrFail($id);
        $this->editingId = $deduction->id;
        $this->form = [
            'employee_id' => $deduction->employee_id,
            'category' => $deduction->category,
            'name' => $deduction->name,
            'amount' => $deduction->amount,
            'recurrence' => $deduction->recurring ? 'recurring' : 'one_time',
            'starts_on' => $deduction->starts_on?->toDateString() ?? '',
            'ends_on' => $deduction->ends_on?->toDateString() ?? '',
            'one_time_date' => $deduction->one_time_date?->toDateString() ?? '',
            'reduces_taxable_income' => $deduction->reduces_taxable_income,
            'reduces_ssc_base' => $deduction->reduces_ssc_base,
            'active' => $deduction->active,
            'notes' => $deduction->notes ?? '',
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'form.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'form.category' => ['required', 'string', 'max:50'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.amount' => ['required', 'decimal:0,3', 'min:0.001'],
            'form.recurrence' => ['required', 'in:recurring,one_time'],
            'form.starts_on' => ['required_if:form.recurrence,recurring', 'nullable', 'date'],
            'form.ends_on' => ['nullable', 'date', 'after_or_equal:form.starts_on'],
            'form.one_time_date' => ['required_if:form.recurrence,one_time', 'nullable', 'date'],
            'form.reduces_taxable_income' => ['boolean'],
            'form.reduces_ssc_base' => ['boolean'],
            'form.active' => ['boolean'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ])['form'];

        $recurring = $data['recurrence'] === 'recurring';
        $payload = [
            'employee_id' => $data['employee_id'],
            'category' => $data['category'],
            'name' => $data['name'],
            'amount' => $data['amount'],
            'recurring' => $recurring,
            'starts_on' => $recurring ? $data['starts_on'] : $data['one_time_date'],
            'ends_on' => $recurring && filled($data['ends_on']) ? $data['ends_on'] : null,
            'one_time_date' => $recurring ? null : $data['one_time_date'],
            'reduces_taxable_income' => (bool) $data['reduces_taxable_income'],
            'reduces_ssc_base' => (bool) $data['reduces_ssc_base'],
            'active' => (bool) $data['active'],
            'notes' => $data['notes'] ?: null,
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            EmployeeDeduction::findOrFail($this->editingId)->update($payload);
        } else {
            EmployeeDeduction::create($payload);
        }

        $this->editingId = null;
        $this->resetForm();
        session()->flash('success', __('hr.saved'));
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-hr');
        EmployeeDeduction::findOrFail($id)->delete();
        session()->flash('success', __('hr.saved'));
    }

    private function resetForm(): void
    {
        $this->form = [
            'employee_id' => '',
            'category' => 'other',
            'name' => '',
            'amount' => '',
            'recurrence' => 'recurring',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => '',
            'one_time_date' => now()->toDateString(),
            'reduces_taxable_income' => false,
            'reduces_ssc_base' => false,
            'active' => true,
            'notes' => '',
        ];
    }

    public function render()
    {
        return view('livewire.deductions.index', [
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(['id', 'name']),
            'rows' => EmployeeDeduction::with('employee')->latest('id')->get(),
        ]);
    }
}
