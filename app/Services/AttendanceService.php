<?php

namespace App\Services;

use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceService
{
    public function assignShift(Employee $employee, Shift $shift, CarbonInterface $from, ?CarbonInterface $to = null): EmployeeShiftAssignment
    {
        if ($to && $to->lt($from)) {
            throw new RuntimeException('Shift assignment end cannot precede start.');
        }

        $overlap = EmployeeShiftAssignment::where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', ($to ?: Carbon::parse('9999-12-31'))->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from->toDateString()))
            ->exists();

        if ($overlap) {
            throw new RuntimeException('Employee already has an overlapping shift assignment.');
        }

        return EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => $from->toDateString(),
            'effective_to' => $to?->toDateString(),
        ]);
    }

    public function shiftFor(Employee $employee, CarbonInterface $date): ?Shift
    {
        return EmployeeShiftAssignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first()?->shift;
    }

    public function recordPunch(Employee $employee, string $type, CarbonInterface $at, string $source = 'manual'): AttendanceEntry
    {
        return DB::transaction(function () use ($employee, $type, $at, $source) {
            $date = $at->toDateString();
            $entry = AttendanceEntry::where('employee_id', $employee->id)
                ->whereDate('work_date', $date)
                ->lockForUpdate()
                ->first();
            $shift = $entry?->shift ?: $this->shiftFor($employee, $at);

            if (! $entry) {
                $entry = AttendanceEntry::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift?->id,
                    'work_date' => $date,
                    'source' => $source,
                    'status' => 'open',
                ]);
            }

            if ($type === 'in') {
                if ($entry->clock_in) {
                    throw new RuntimeException('Clock-in already recorded for this work date.');
                }
                $entry->clock_in = $at;
            } elseif ($type === 'out') {
                if (! $entry->clock_in) {
                    throw new RuntimeException('Clock-out cannot precede clock-in.');
                }
                if ($entry->clock_out) {
                    throw new RuntimeException('Clock-out already recorded for this work date.');
                }
                if ($at->lte($entry->clock_in)) {
                    throw new RuntimeException('Clock-out must be after clock-in.');
                }
                $entry->clock_out = $at;
            } else {
                throw new RuntimeException('Punch type must be in or out.');
            }

            $this->recalculate($entry, $shift);
            $entry->save();

            return $entry->refresh();
        });
    }

    public function recalculate(AttendanceEntry $entry, ?Shift $shift = null): AttendanceEntry
    {
        $shift ??= $entry->shift;
        $entry->worked_minutes = 0;
        $entry->late_minutes = 0;
        $entry->early_leave_minutes = 0;

        if ($entry->clock_in && $entry->clock_out) {
            $entry->worked_minutes = max(0, (int) $entry->clock_in->diffInMinutes($entry->clock_out) - (int) ($shift?->break_minutes ?? 0));
        }

        if ($shift && $entry->clock_in) {
            $start = Carbon::parse($entry->work_date->toDateString().' '.$shift->start_time);
            $allowed = $start->copy()->addMinutes((int) $shift->grace_minutes);
            if ($entry->clock_in->gt($allowed)) {
                $entry->late_minutes = (int) $start->diffInMinutes($entry->clock_in);
            }
        }

        if ($shift && $entry->clock_out) {
            $end = Carbon::parse($entry->work_date->toDateString().' '.$shift->end_time);
            if ($end->lte(Carbon::parse($entry->work_date->toDateString().' '.$shift->start_time))) {
                $end->addDay();
            }
            if ($entry->clock_out->lt($end)) {
                $entry->early_leave_minutes = (int) $entry->clock_out->diffInMinutes($end);
            }
        }

        if (! $entry->clock_in || ! $entry->clock_out) {
            $entry->status = 'open';
        } elseif ($entry->late_minutes > 0 || $entry->early_leave_minutes > 0) {
            $entry->status = 'exception';
        } else {
            $entry->status = 'complete';
        }

        return $entry;
    }

    public function approve(AttendanceEntry $entry, int $userId): AttendanceEntry
    {
        if (! $entry->clock_in || ! $entry->clock_out) {
            throw new RuntimeException('Incomplete attendance cannot be approved.');
        }
        $entry->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);

        return $entry->refresh();
    }
}
