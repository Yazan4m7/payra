<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OnboardingTask;

class OnboardingService
{
    public function createFor(Employee $employee): void
    {
        OnboardingTask::firstOrCreate(
            ['employee_id' => $employee->id, 'task_key' => 'ssc_registration'],
            [
                'title_ar' => 'تسجيل الموظف في الضمان الاجتماعي',
                'title_en' => 'Register employee with SSC',
                'due_date' => $employee->hire_date,
            ]
        );

        $this->syncSscRegistration($employee);
    }

    public function syncSscRegistration(Employee $employee, ?int $completedBy = null): void
    {
        $task = OnboardingTask::firstOrCreate(
            ['employee_id' => $employee->id, 'task_key' => 'ssc_registration'],
            [
                'title_ar' => 'تسجيل الموظف في الضمان الاجتماعي',
                'title_en' => 'Register employee with SSC',
                'due_date' => $employee->hire_date,
            ]
        );

        if ($employee->isSscRegistered() && ! $task->completed_at) {
            $task->update(['completed_at' => now(), 'completed_by' => $completedBy]);
        }

        if (! $employee->isSscRegistered() && $task->completed_at) {
            $task->update(['completed_at' => null, 'completed_by' => null]);
        }
    }
}
