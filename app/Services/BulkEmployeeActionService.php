<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BulkEmployeeActionService
{
    public function __construct(private SalaryHistoryService $salaries) {}

    public function execute(array $employeeIds, string $action, array $payload, ?int $actorId): int
    {
        $ids = collect($employeeIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();
        if ($ids === []) {
            throw new RuntimeException('Select at least one employee.');
        }

        return DB::transaction(function () use ($ids, $action, $payload, $actorId) {
            $employees = Employee::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($employees->count() !== count($ids)) {
                throw new RuntimeException('One or more selected employees no longer exist.');
            }

            if ($action === 'organization') {
                $organization = $this->resolveOrganization($payload);
                foreach ($employees as $employee) {
                    $employee->update($organization);
                }
            } elseif ($action === 'status') {
                $status = (string) ($payload['status'] ?? '');
                if (! in_array($status, ['active', 'on_leave', 'inactive'], true)) {
                    throw new RuntimeException('Bulk termination is not allowed. Use the termination workflow for each employee.');
                }
                foreach ($employees as $employee) {
                    $employee->update(['status' => $status]);
                    if ($employee->user) {
                        $employee->user->update(['active' => $status !== 'inactive']);
                    }
                }
            } elseif ($action === 'salary') {
                $amount = trim((string) ($payload['amount'] ?? ''));
                $effectiveFrom = Carbon::parse((string) ($payload['effective_from'] ?? ''));
                $reason = trim((string) ($payload['reason'] ?? 'Bulk salary change'));
                foreach ($employees as $employee) {
                    $this->salaries->record($employee, $amount, $effectiveFrom, $reason, $actorId);
                }
            } else {
                throw new RuntimeException('Unsupported bulk employee action.');
            }

            return $employees->count();
        });
    }

    private function resolveOrganization(array $payload): array
    {
        $branchId = filled($payload['branch_id'] ?? null) ? (int) $payload['branch_id'] : null;
        $departmentId = filled($payload['department_id'] ?? null) ? (int) $payload['department_id'] : null;
        $costCenterId = filled($payload['cost_center_id'] ?? null) ? (int) $payload['cost_center_id'] : null;

        $costCenter = $costCenterId ? CostCenter::findOrFail($costCenterId) : null;
        if ($costCenter?->department_id) {
            if ($departmentId && $departmentId !== (int) $costCenter->department_id) {
                throw new RuntimeException('The selected cost center does not belong to the selected department.');
            }
            $departmentId = (int) $costCenter->department_id;
        }

        $department = $departmentId ? Department::findOrFail($departmentId) : null;
        if ($department?->branch_id) {
            if ($branchId && $branchId !== (int) $department->branch_id) {
                throw new RuntimeException('The selected department does not belong to the selected branch.');
            }
            $branchId = (int) $department->branch_id;
        }

        if ($branchId) {
            Branch::findOrFail($branchId);
        }

        return ['branch_id' => $branchId, 'department_id' => $departmentId, 'cost_center_id' => $costCenterId];
    }
}
