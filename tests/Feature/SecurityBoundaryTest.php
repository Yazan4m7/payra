<?php

namespace Tests\Feature;

use App\Models\BiometricEvent;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Models\Tenant;
use App\Services\BiometricDeviceService;
use App\Services\BiometricIngestService;
use App\Services\PerformanceReviewService;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class SecurityBoundaryTest extends TestCase
{
    public function test_biometric_auth_unknown_mapping_and_replay_are_fail_closed_and_idempotent(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'Security '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $employee = Employee::create([
                'name' => 'Clocked Employee',
                'national_id' => 'SEC-1',
                'ssc_number' => 'SEC-SSC-1',
                'ssc_enrollment_date' => '2020-01-01',
                'hire_date' => '2026-01-01',
                'salary' => '500.000',
                'status' => 'active',
            ]);
            $deviceResult = app(BiometricDeviceService::class)->create('Gate A');
            $device = $deviceResult['device'];
            $token = $deviceResult['token'];
            $ingest = app(BiometricIngestService::class);
            $payload = ['event_id' => 'evt-1', 'employee_id' => 'EXT-1', 'event_type' => 'in', 'event_at' => '2026-09-05T08:00:00+03:00'];

            try {
                $ingest->ingest($device, 'wrong-token', $payload);
                $this->fail('Invalid biometric token must be rejected.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('credentials', $e->getMessage());
            }
            $this->assertSame(0, BiometricEvent::count());

            try {
                $ingest->ingest($device, $token, $payload);
                $this->fail('Unknown employee mapping must be rejected.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('mapping', $e->getMessage());
            }
            $this->assertSame(0, BiometricEvent::count());

            app(BiometricDeviceService::class)->map($device, $employee, 'EXT-1');
            $first = $ingest->ingest($device, $token, $payload);
            $replay = $ingest->ingest($device, $token, $payload);
            $this->assertSame($first->id, $replay->id);
            $this->assertSame(1, BiometricEvent::count());
            $this->assertSame(1, $employee->fresh()->getConnection()->table('attendance_entries')->where('employee_id', $employee->id)->count());
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }

    public function test_employee_cannot_acknowledge_another_employees_performance_review(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'Perf Security '.Str::random(5)]);
        try {
            tenancy()->initialize($tenant);
            $owner = Employee::create(['name' => 'Owner', 'national_id' => 'PERF-OWNER', 'hire_date' => '2026-01-01', 'salary' => '500.000', 'status' => 'active']);
            $other = Employee::create(['name' => 'Other', 'national_id' => 'PERF-OTHER', 'hire_date' => '2026-01-01', 'salary' => '500.000', 'status' => 'active']);
            $cycle = PerformanceCycle::create(['name' => 'Security Cycle', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'open']);
            $review = PerformanceReview::create([
                'performance_cycle_id' => $cycle->id,
                'employee_id' => $owner->id,
                'status' => 'submitted',
                'overall_rating' => '4.00',
                'goals_rating' => '4.00',
                'competency_rating' => '4.00',
                'submitted_at' => now(),
            ]);

            try {
                app(PerformanceReviewService::class)->acknowledge($review, $other, 'Not mine');
                $this->fail('Cross-employee review acknowledgement must be rejected.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('does not belong', $e->getMessage());
            }
            $this->assertSame('submitted', $review->fresh()->status);
            $this->assertNull($review->fresh()->acknowledged_at);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }
}
