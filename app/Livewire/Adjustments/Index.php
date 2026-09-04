<?php

namespace App\Livewire\Adjustments;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Services\PayrollAdjustmentService;
use Livewire\Component;

class Index extends Component
{
    public array $form = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'form.employee_id' => ['required', 'exists:employees,id'],
            'form.kind' => ['required', 'in:earning,deduction'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.amount' => ['required', 'decimal:0,3', 'min:0.001'],
            'form.payment_month' => ['required', 'integer', 'between:1,12'],
            'form.payment_year' => ['required', 'integer', 'between:2000,2200'],
            'form.source_month' => ['nullable', 'integer', 'between:1,12'],
            'form.source_year' => ['nullable', 'integer', 'between:2000,2200'],
            'form.taxable' => ['boolean'],
            'form.ssc_applicable' => ['boolean'],
            'form.reduces_taxable_income' => ['boolean'],
            'form.reduces_ssc_base' => ['boolean'],
            'form.reason' => ['required', 'string', 'max:2000'],
        ])['form'];

        PayrollAdjustment::create($data + ['status' => 'pending', 'created_by' => auth()->id()]);
        $this->resetForm();
        session()->flash('success', __('hr.saved'));
    }

    public function approve(int $id, PayrollAdjustmentService $service): void
    {
        $this->authorize('manage-hr');
        try {
            $service->approve(PayrollAdjustment::findOrFail($id), auth()->id());
            session()->flash('success', __('hr.approved'));
        } catch (\RuntimeException $e) {
            $this->addError('adjustment', $e->getMessage());
        }
    }

    public function void(int $id, PayrollAdjustmentService $service): void
    {
        $this->authorize('manage-hr');
        try {
            $service->void(PayrollAdjustment::findOrFail($id));
            session()->flash('success', __('hr.saved'));
        } catch (\RuntimeException $e) {
            $this->addError('adjustment', $e->getMessage());
        }
    }

    private function resetForm(): void
    {
        $this->form = [
            'employee_id' => '', 'kind' => 'earning', 'name' => '', 'amount' => '',
            'payment_month' => (int) now()->month, 'payment_year' => (int) now()->year,
            'source_month' => '', 'source_year' => '', 'taxable' => true, 'ssc_applicable' => true,
            'reduces_taxable_income' => false, 'reduces_ssc_base' => false, 'reason' => '',
        ];
    }

    public function render()
    {
        return view('livewire.adjustments.index', [
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'rows' => PayrollAdjustment::with('employee')->latest('id')->get(),
        ]);
    }
}
