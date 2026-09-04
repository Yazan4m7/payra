<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveService
{
    public function __construct(private ComplianceSettingsService $compliance) {}

    public function ensureBalance(Employee $employee, int $year): LeaveBalance
    {
        $attributes = ['employee_id' => $employee->id, 'year' => $year];
        $existing = LeaveBalance::where($attributes)->first();
        if ($existing) {
            return $existing;
        }

        $setting = $this->compliance->forDate(Carbon::create($year, 1, 1));
        $s = $setting->settings;
        $this->compliance->require($s, ['annual_leave_tiers', 'sick_leave_paid_days', 'sick_leave_hospital_extra_days']);

        $asOf = Carbon::create($year, 1, 1);
        $years = $employee->hire_date->isAfter($asOf) ? 0 : $employee->hire_date->diffInYears($asOf);
        $annual = '0';
        foreach ($s['annual_leave_tiers'] as $tier) {
            if ($years >= (int) $tier['min_years']) {
                $annual = (string) $tier['days'];
            }
        }

        return LeaveBalance::firstOrCreate($attributes, [
            'compliance_setting_id' => $setting->id,
            'annual_entitlement' => $annual,
            'annual_remaining' => $annual,
            'sick_entitlement' => (string) $s['sick_leave_paid_days'],
            'sick_remaining' => (string) $s['sick_leave_paid_days'],
            'hospital_extra_entitlement' => (string) $s['sick_leave_hospital_extra_days'],
            'hospital_extra_remaining' => (string) $s['sick_leave_hospital_extra_days'],
        ]);
    }


    public function createRequest(Employee $employee, string $type, Carbon $start, Carbon $end, bool $hospitalized = false, ?string $reason = null): LeaveRequest
    {
        if ($start->year !== $end->year) {
            throw new RuntimeException(__('hr.leave_same_year'));
        }
        if ($end->lt($start)) {
            throw new RuntimeException('Leave end date cannot be before start date.');
        }

        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            throw new RuntimeException(__('hr.error_leave_overlap'));
        }

        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'days' => 0,
            'hospitalized' => $hospitalized,
            'status' => 'pending',
            'reason' => $reason,
        ]);
    }

    public function requestedDays(LeaveRequest $request): int
    {
        $s = $this->compliance->forDate($request->start_date)->settings;
        $this->compliance->require($s, ['weekly_rest_days', 'leave_count_public_holidays', 'leave_count_weekly_rest_days']);

        $rest = array_map('intval', $s['weekly_rest_days']);
        $countHolidays = (bool) $s['leave_count_public_holidays'];
        $countRest = (bool) $s['leave_count_weekly_rest_days'];
        $holidays = PublicHoliday::query()
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $days = 0;
        foreach (CarbonPeriod::create($request->start_date, $request->end_date) as $date) {
            if (! $countRest && in_array($date->dayOfWeekIso, $rest, true)) {
                continue;
            }
            if (! $countHolidays && in_array($date->toDateString(), $holidays, true)) {
                continue;
            }
            $days++;
        }

        return $days;
    }

    public function approve(LeaveRequest $request, int $approverId): LeaveRequest
    {
        if ($request->status === 'approved') {
            return $request;
        }

        return DB::transaction(function () use ($request, $approverId) {
            $request->refresh();
            if ($request->status !== 'pending') {
                throw new RuntimeException('Only pending leave can be approved.');
            }
            if ($request->start_date->year !== $request->end_date->year) {
                throw new RuntimeException('A leave request must stay within one balance year.');
            }

            $dayCount = $this->requestedDays($request);
            if ($dayCount <= 0) {
                throw new RuntimeException(__('hr.error_leave_no_days'));
            }
            $days = (string) $dayCount;
            $this->ensureBalance($request->employee, $request->start_date->year);
            $balance = LeaveBalance::query()
                ->where('employee_id', $request->employee_id)
                ->where('year', $request->start_date->year)
                ->lockForUpdate()
                ->firstOrFail();

            $requested = Decimal::d($days);
            if ($request->type === 'annual') {
                $remaining = Decimal::d($balance->annual_remaining);
                if ($remaining->isLessThan($requested)) {
                    throw new RuntimeException(__('hr.error_leave_annual_balance'));
                }
                $balance->annual_used = Decimal::add($balance->annual_used, $days);
                $balance->annual_remaining = Decimal::sub($balance->annual_remaining, $days);
            } else {
                $remaining = Decimal::d($balance->sick_remaining);
                if ($remaining->isGreaterThanOrEqualTo($requested)) {
                    $balance->sick_used = Decimal::add($balance->sick_used, $days);
                    $balance->sick_remaining = Decimal::sub($balance->sick_remaining, $days);
                } else {
                    $extra = $requested->minus($remaining);
                    $extraRemaining = Decimal::d($balance->hospital_extra_remaining);
                    if (! $request->hospitalized || $extraRemaining->isLessThan($extra)) {
                        throw new RuntimeException(__('hr.error_leave_sick_balance'));
                    }
                    $balance->sick_used = Decimal::add($balance->sick_used, (string) $remaining);
                    $balance->sick_remaining = '0.00';
                    $balance->hospital_extra_used = Decimal::add($balance->hospital_extra_used, (string) $extra);
                    $balance->hospital_extra_remaining = Decimal::sub($balance->hospital_extra_remaining, (string) $extra);
                }
            }

            $balance->save();
            $request->update([
                'days' => $days,
                'status' => 'approved',
                'approver_id' => $approverId,
                'approved_at' => now(),
            ]);

            return $request->refresh();
        });
    }
}
