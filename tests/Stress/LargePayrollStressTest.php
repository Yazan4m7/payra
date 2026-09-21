<?php

namespace Tests\Stress;

use App\Models\Tenant;
use App\Models\User;
use App\Services\PayrollReportingService;
use App\Services\PayrollRunService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\ComplianceFixture;
use Tests\TestCase;

class LargePayrollStressTest extends TestCase
{
    public function test_500_employee_payroll_calculates_reconciles_and_locks_without_duplication(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires MySQL.');
        }

        $tenant = Tenant::create(['name' => 'Stress '.Str::random(6)]);
        $started = microtime(true);
        try {
            tenancy()->initialize($tenant);
            $user = User::create([
                'name' => 'Stress Approver',
                'email' => Str::uuid().'@example.test',
                'password' => 'secret123',
                'role' => 'company_admin',
                'active' => true,
            ]);
            ComplianceFixture::create($user->id);

            $now = now();
            foreach (array_chunk(range(1, 500), 100) as $chunk) {
                $rows = [];
                foreach ($chunk as $i) {
                    $rows[] = [
                        'name' => 'Stress Employee '.$i,
                        'national_id' => 'STRESS-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                        'ssc_number' => 'SSC-STRESS-'.$i,
                        'ssc_enrollment_date' => '2020-01-01',
                        'hire_date' => '2026-01-01',
                        'salary' => number_format(350 + ($i % 300) + (($i % 7) / 10), 3, '.', ''),
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('employees')->insert($rows);
            }

            $service = app(PayrollRunService::class);
            $run = $service->createRun(9, 2026, $user->id);
            $run = $service->process($run);
            $this->assertSame(500, $run->payslips()->count());

            $reconciliation = app(PayrollReportingService::class)->reconcileRun($run);
            $this->assertTrue($reconciliation['ok'], json_encode($reconciliation['failures']));
            $this->assertSame(500, $reconciliation['checked']);

            $approved = $service->approve($run, $user->id);
            $this->assertTrue($approved->isLocked());
            $this->assertSame('approved', $approved->status);

            $again = $service->approve($approved, $user->id);
            $this->assertSame($approved->id, $again->id);
            $this->assertSame(500, $again->payslips()->count());

            fwrite(STDERR, sprintf("\n500-employee payroll stress cycle completed in %.2fs\n", microtime(true) - $started));
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant->delete();
        }
    }
}
