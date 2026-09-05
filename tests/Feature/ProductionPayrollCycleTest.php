<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\GlAccountMapping;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GlExportService;
use App\Services\PayrollReportingService;
use App\Services\PayrollRunService;
use Illuminate\Support\Str;
use Tests\Support\ComplianceFixture;
use Tests\TestCase;

class ProductionPayrollCycleTest extends TestCase
{
    public function test_full_payroll_lifecycle_reconciles_locks_is_idempotent_and_exports_balanced_gl(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'E2E '.Str::random(6)]);
        try {
            tenancy()->initialize($tenant);
            $user = User::create([
                'name' => 'Payroll Approver',
                'email' => Str::uuid().'@example.test',
                'password' => 'secret123',
                'role' => 'company_admin',
                'active' => true,
            ]);
            ComplianceFixture::create($user->id);

            $employees = collect(range(1, 25))->map(fn (int $i) => Employee::create([
                'name' => 'E2E Employee '.$i,
                'national_id' => 'E2E-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'ssc_number' => 'SSC-E2E-'.$i,
                'ssc_enrollment_date' => '2020-01-01',
                'hire_date' => '2026-01-01',
                'salary' => number_format(400 + ($i * 7.125), 3, '.', ''),
                'status' => 'active',
            ]));

            foreach (GlExportService::REQUIRED as $index => $key) {
                GlAccountMapping::create([
                    'key' => $key,
                    'account_code' => (string) (5000 + $index),
                    'account_name' => $key,
                ]);
            }

            $runs = app(PayrollRunService::class);
            $reporting = app(PayrollReportingService::class);
            $run = $runs->createRun(9, 2026, $user->id);
            $run = $runs->process($run);

            $this->assertSame('calculated', $run->status);
            $this->assertSame(25, $run->payslips()->count());
            $this->assertNotEmpty($run->calculation_hash);

            $reconciliation = $reporting->reconcileRun($run);
            $this->assertTrue($reconciliation['ok'], json_encode($reconciliation['failures']));
            $this->assertSame(25, $reconciliation['checked']);

            $approved = $runs->approve($run, $user->id);
            $this->assertSame('approved', $approved->status);
            $this->assertTrue($approved->isLocked());
            $this->assertNotNull($approved->approved_at);
            $this->assertNotNull($approved->locked_at);

            $secondApproval = $runs->approve($approved, $user->id);
            $this->assertSame($approved->id, $secondApproval->id);
            $this->assertSame(25, $secondApproval->payslips()->count());

            $ytd = $reporting->ytdForEmployee($employees->first(), 2026, 9);
            $this->assertSame(1, $ytd['payroll_count']);
            $this->assertGreaterThan(0, (float) $ytd['net_salary']);

            $journal = app(GlExportService::class)->lines($secondApproval);
            $this->assertSame($journal['total_debit'], $journal['total_credit']);
            $this->assertNotEmpty($journal['lines']);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }
}
