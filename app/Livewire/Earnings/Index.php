<?php

namespace App\Livewire\Earnings;

use App\Models\Employee;
use App\Models\EmployeeEarning;
use Illuminate\Validation\Rule;
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
        $earning = EmployeeEarning::findOrFail($id);
        $this->editingId = $earning->id;
        $this->form = [
            'employee_id' => $earning->employee_id,
            'category' => $earning->category,
            'name' => $earning->name,
            'amount' => $earning->amount,
            'recurrence' => $earning->recurring ? 'recurring' : 'one_time',
            'starts_on' => $earning->starts_on?->toDateString() ?? '',
            'ends_on' => $earning->ends_on?->toDateString() ?? '',
            'one_time_date' => $earning->one_time_date?->toDateString() ?? '',
            'taxable' => $earning->taxable,
            'ssc_applicable' => $earning->ssc_applicable,
            'active' => $earning->active,
            'notes' => $earning->notes ?? '',
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
            'form.category' => ['required', Rule::in(['allowance', 'bonus', 'commission'])],
            'form.name' => ['required', 'string', 'max:255'],
            'form.amount' => ['required', 'decimal:0,3', 'min:0.001'],
            'form.recurrence' => ['required', Rule::in(['recurring', 'one_time'])],
            'form.starts_on' => ['required_if:form.recurrence,recurring', 'nullable', 'date'],
            'form.ends_on' => ['nullable', 'date', 'after_or_equal:form.starts_on'],
            'form.one_time_date' => ['required_if:form.recurrence,one_time', 'nullable', 'date'],
            'form.taxable' => ['boolean'],
            'form.ssc_applicable' => ['boolean'],
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
            'taxable' => (bool) $data['taxable'],
            'ssc_applicable' => (bool) $data['ssc_applicable'],
            'active' => (bool) $data['active'],
            'notes' => $data['notes'] ?: null,
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            EmployeeEarning::findOrFail($this->editingId)->update($payload);
        } else {
            EmployeeEarning::create($payload);
        }

        $this->editingId = null;
        $this->resetForm();
        session()->flash('success', __('hr.saved'));
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-hr');
        EmployeeEarning::findOrFail($id)->delete();
        session()->flash('success', __('hr.saved'));
    }

    private function resetForm(): void
    {
        $this->form = [
            'employee_id' => '',
            'category' => 'allowance',
            'name' => '',
            'amount' => '',
            'recurrence' => 'recurring',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => '',
            'one_time_date' => now()->toDateString(),
            'taxable' => true,
            'ssc_applicable' => true,
            'active' => true,
            'notes' => '',
        ];
    }

    public function render()
    {
        return view('livewire.earnings.index', [
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(['id', 'name']),
            'rows' => EmployeeEarning::with('employee')->latest('id')->get(),
        ]);
    }
}
