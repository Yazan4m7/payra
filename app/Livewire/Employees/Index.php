<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function startEdit(int $id): void
    {
        $this->authorize('manage-hr');
        $employee = Employee::with('user')->findOrFail($id);
        $this->editingId = $employee->id;
        $this->form = [
            'name' => $employee->name,
            'national_id' => $employee->national_id,
            'ssc_number' => $employee->ssc_number ?? '',
            'ssc_enrollment_date' => $employee->ssc_enrollment_date?->toDateString() ?? '',
            'hire_date' => $employee->hire_date?->toDateString() ?? '',
            'job_title' => $employee->job_title ?? '',
            'salary' => $employee->salary,
            'bank_iban' => $employee->bank_iban ?? '',
            'status' => $employee->status,
            'email' => $employee->user?->email ?? '',
            'password' => '',
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(OnboardingService $onboarding, LeaveService $leave): void
    {
        $this->authorize('manage-hr');
        $employee = $this->editingId ? Employee::with('user')->findOrFail($this->editingId) : null;
        $userId = $employee?->user_id;

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.national_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'national_id')->ignore($employee?->id)],
            'form.email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'form.password' => [$employee?->user ? 'nullable' : 'required_with:form.email', 'nullable', 'string', 'min:8'],
            'form.salary' => ['required', 'decimal:0,3', 'min:0'],
            'form.hire_date' => ['required', 'date'],
            'form.ssc_enrollment_date' => ['nullable', 'date'],
            'form.ssc_number' => ['nullable', 'string', 'max:100'],
            'form.job_title' => ['nullable', 'string', 'max:255'],
            'form.bank_iban' => ['nullable', 'string', 'max:64'],
            'form.status' => ['required', Rule::in(['active', 'on_leave', 'terminated', 'inactive'])],
        ])['form'];

        DB::transaction(function () use ($employee, $data, $onboarding, $leave) {
            $user = $employee?->user;
            if (filled($data['email'])) {
                $userPayload = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 'employee',
                    'locale' => $user?->locale ?? 'ar',
                    'active' => ! in_array($data['status'], ['inactive', 'terminated'], true),
                ];
                if (filled($data['password'])) {
                    $userPayload['password'] = $data['password'];
                }
                if ($user) {
                    $user->update($userPayload);
                } else {
                    $userPayload['password'] = $data['password'];
                    $user = User::create($userPayload);
                }
            } elseif ($user) {
                $user->update(['name' => $data['name'], 'active' => false]);
            }

            $payload = [
                'user_id' => $user?->id,
                'name' => $data['name'],
                'national_id' => $data['national_id'],
                'ssc_number' => $data['ssc_number'] ?: null,
                'ssc_enrollment_date' => $data['ssc_enrollment_date'] ?: null,
                'hire_date' => $data['hire_date'],
                'job_title' => $data['job_title'] ?: null,
                'salary' => $data['salary'],
                'bank_iban' => $data['bank_iban'] ?: null,
                'status' => $data['status'],
            ];

            if ($employee) {
                $employee->update($payload);
            } else {
                $employee = Employee::create($payload);
                try {
                    $leave->ensureBalance($employee, (int) now()->year);
                } catch (\RuntimeException) {
                    // A company may create employees before its first compliance version.
                    // The dashboard will keep flagging the missing settings until configured.
                }
            }

            $onboarding->createFor($employee);
            $onboarding->syncSscRegistration($employee, auth()->id());
        });

        $this->editingId = null;
        $this->resetForm();
        session()->flash('success', __('hr.saved'));
    }

    private function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'national_id' => '',
            'ssc_number' => '',
            'ssc_enrollment_date' => '',
            'hire_date' => '',
            'job_title' => '',
            'salary' => '',
            'bank_iban' => '',
            'status' => 'active',
            'email' => '',
            'password' => '',
        ];
    }

    public function render()
    {
        return view('livewire.employees.index', [
            'rows' => Employee::query()
                ->with(['user', 'onboardingTasks' => fn ($q) => $q->where('task_key', 'ssc_registration')])
                ->when($this->search, function ($query) {
                    $term = '%'.$this->search.'%';
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', $term)
                            ->orWhere('national_id', 'like', $term)
                            ->orWhere('ssc_number', 'like', $term)
                            ->orWhere('job_title', 'like', $term);
                    });
                })
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
