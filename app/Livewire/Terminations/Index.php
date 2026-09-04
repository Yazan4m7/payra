<?php

namespace App\Livewire\Terminations;

use App\Models\Employee;
use App\Models\TerminationRecord;
use App\Services\TerminationService;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public ?int $employee_id = null;
    public string $termination_date = '';
    public string $reason = '';
    public string $notice_given_at = '';

    public function save(TerminationService $service): void
    {
        $this->authorize('manage-hr');
        $data = $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'termination_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notice_given_at' => ['nullable', 'date', 'before_or_equal:termination_date'],
        ]);

        try {
            $service->record(
                Employee::findOrFail($data['employee_id']),
                Carbon::parse($data['termination_date']),
                $data['reason'],
                $data['notice_given_at'] ? Carbon::parse($data['notice_given_at']) : null,
                auth()->id()
            );
            $this->reset('employee_id', 'termination_date', 'reason', 'notice_given_at');
            session()->flash('success', __('hr.saved'));
        } catch (\RuntimeException $e) {
            $this->addError('termination', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.terminations.index', [
            'employees' => Employee::whereIn('status', ['active', 'on_leave'])->orderBy('name')->get(),
            'records' => TerminationRecord::with('employee')->latest('termination_date')->take(30)->get(),
        ]);
    }
}
