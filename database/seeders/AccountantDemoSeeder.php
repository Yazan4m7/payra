<?php

namespace Database\Seeders;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeEntry;
use App\Models\PayrollRun;
use App\Models\PublicHoliday;
use App\Models\TerminationRecord;
use App\Models\User;
use App\Services\ComplianceSettingsService;
use App\Services\LeaveService;
use App\Services\OnboardingService;
use App\Services\OvertimeService;
use App\Services\PayrollRunService;
use App\Services\TerminationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountantDemoSeeder extends Seeder
{
    private const HR_EMAIL = 'accountant.demo@almaher.localhost';
    private const HR_PASSWORD = 'AccountantDemo2026!';
    private const EMPLOYEE_PASSWORD = 'EmployeeDemo2026!';

    public function run(): void
    {
        if (! tenancy()->initialized) {
            throw new \RuntimeException('AccountantDemoSeeder must run inside an initialized tenant.');
        }

        tenant()->update([
            'name' => 'Al Maher Trading & Services (Demo)',
            'sector' => 'Professional services — fictional demonstration company',
            'ssc_establishment_number' => 'DEMO-SSC-EST-2026',
            'plan' => 'standard',
            'subscription_status' => 'trial',
        ]);

        $admin = User::where('role', 'company_admin')->firstOrFail();
        $accountant = User::updateOrCreate(
            ['email' => self::HR_EMAIL],
            [
                'name' => 'Demo Payroll Accountant',
                'password' => self::HR_PASSWORD,
                'role' => 'hr',
                'locale' => 'en',
                'active' => true,
            ]
        );

        $compliance = app(ComplianceSettingsService::class);
        $settings2025 = $compliance->validatePayload($this->illustrativeSettings('2800.000', '5000.000'));
        $settings2026 = $compliance->validatePayload($this->illustrativeSettings('3000.000', '5500.000'));

        ComplianceSetting::firstOrCreate(
            ['version_label' => 'DEMO-2025 — Illustrative only', 'effective_date' => '2025-01-01'],
            ['settings' => $settings2025, 'created_by' => $admin->id]
        );
        ComplianceSetting::firstOrCreate(
            ['version_label' => 'DEMO-2026 — Illustrative only', 'effective_date' => '2026-01-01'],
            ['settings' => $settings2026, 'created_by' => $admin->id]
        );

        $this->seedHolidays();
        $employees = $this->seedEmployees();

        $leave = app(LeaveService::class);
        $onboarding = app(OnboardingService::class);
        foreach ($employees as $employee) {
            $onboarding->createFor($employee);
            $onboarding->syncSscRegistration($employee, $admin->id);
            $leave->ensureBalance($employee, 2025);
            $leave->ensureBalance($employee, 2026);
        }

        $this->seedLeave($employees, $leave, $accountant->id);
        $this->seedOvertime($employees, app(OvertimeService::class), $accountant->id);
        $this->seedPayroll($accountant->id);
        $this->seedTermination($employees, app(TerminationService::class), $accountant->id);

        $counts = collect([
            'employees' => Employee::count(),
            'payroll_runs' => PayrollRun::count(),
            'payslips' => \App\Models\Payslip::count(),
            'leave_requests' => LeaveRequest::count(),
            'overtime_entries' => OvertimeEntry::count(),
            'termination_records' => TerminationRecord::count(),
        ])->map(fn ($count, $label) => "{$label}={$count}")->implode(', ');

        $this->command?->info('Accountant demo ready: '.$counts);
    }

    private function illustrativeSettings(string $postCutoffCeiling, string $preCutoffCeiling): array
    {
        return [
            // These values are deliberately labelled as illustrative demo inputs.
            'ssc_employee_percent' => '7.500000',
            'ssc_employer_percent' => '14.250000',
            'ssc_enrollment_cutoff_date' => '2010-05-01',
            'ssc_ceiling_pre_cutoff_jod' => $preCutoffCeiling,
            'ssc_ceiling_post_cutoff_jod' => $postCutoffCeiling,
            'income_tax_brackets' => [
                ['up_to_jod' => '5000.000', 'rate_percent' => '5.000000'],
                ['up_to_jod' => '10000.000', 'rate_percent' => '10.000000'],
                ['up_to_jod' => '15000.000', 'rate_percent' => '15.000000'],
                ['up_to_jod' => '20000.000', 'rate_percent' => '20.000000'],
                ['up_to_jod' => null, 'rate_percent' => '25.000000'],
            ],
            'personal_exemption_annual_jod' => '9000.000',
            'high_earner_surcharge_threshold_annual_jod' => '70000.000',
            'high_earner_surcharge_percent' => '1.000000',
            'annual_leave_tiers' => [
                ['min_years' => 0, 'days' => '14.00'],
                ['min_years' => 5, 'days' => '21.00'],
            ],
            'sick_leave_paid_days' => '14.00',
            'sick_leave_hospital_extra_days' => '14.00',
            'overtime_multiplier_standard' => '1.250000',
            'overtime_multiplier_rest_holiday' => '1.500000',
            'overtime_daily_cap_hours' => '6.00',
            'overtime_weekly_cap_hours' => '24.00',
            'overtime_monthly_cap_hours' => '24.00',
            'overtime_warning_percent' => '80.00',
            'notice_period_days' => 30,
            'monthly_hours_divisor' => '240.000',
            'salary_daily_divisor' => '30.000',
            'weekly_rest_days' => [5],
            'leave_count_public_holidays' => false,
            'leave_count_weekly_rest_days' => false,
            'filing_deadlines' => [
                [
                    'name' => 'Demo monthly payroll review / مراجعة الرواتب التجريبية',
                    'rule' => 'Illustrative workflow: approve payroll by the fifth business day.',
                    'due_day' => 5,
                ],
                [
                    'name' => 'Demo SSC file preparation / ملف الضمان التجريبي',
                    'rule' => 'Illustrative workflow only; verify the legal deadline before real filing.',
                    'due_day' => 15,
                ],
                [
                    'name' => 'Demo withholding reconciliation / مطابقة الاقتطاعات',
                    'rule' => 'Illustrative quarterly accountant review.',
                    'due_day' => null,
                ],
            ],
        ];
    }

    private function seedHolidays(): void
    {
        $holidays = [
            ['2025-01-01', 'عطلة تجريبية — بداية السنة', 'Demo holiday — New Year'],
            ['2025-05-01', 'عطلة تجريبية — يوم العمال', 'Demo holiday — Labour Day'],
            ['2025-12-25', 'عطلة تجريبية — نهاية السنة', 'Demo holiday — Year end'],
            ['2026-01-01', 'عطلة تجريبية — بداية السنة', 'Demo holiday — New Year'],
            ['2026-03-01', 'عطلة تجريبية — يوم الشركة', 'Demo holiday — Company Day'],
            ['2026-05-01', 'عطلة تجريبية — يوم العمال', 'Demo holiday — Labour Day'],
            ['2026-05-25', 'عطلة تجريبية — مناسبة وطنية', 'Demo holiday — National occasion'],
            ['2026-09-02', 'عطلة تجريبية — تدريب داخلي', 'Demo holiday — Internal training'],
            ['2026-12-25', 'عطلة تجريبية — نهاية السنة', 'Demo holiday — Year end'],
        ];

        foreach ($holidays as [$date, $nameAr, $nameEn]) {
            PublicHoliday::updateOrCreate(
                ['date' => $date],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'year' => (int) substr($date, 0, 4)]
            );
        }
    }

    /** @return array<string, Employee> */
    private function seedEmployees(): array
    {
        $rows = [
            ['أحمد المثال', 'Payroll Supervisor', '1850.000', '2014-03-01', 'registered', true],
            ['ليان النموذج', 'Senior Accountant', '2250.000', '2017-06-15', 'registered', true],
            ['سامر التجريبي', 'Finance Manager', '4200.000', '2012-01-10', 'registered', true],
            ['نور العرض', 'HR Officer', '1350.000', '2021-09-05', 'registered', true],
            ['عمر البيانات', 'Sales Manager', '2650.000', '2018-02-18', 'registered', true],
            ['دانا المحاسبة', 'Accounts Payable Officer', '1150.000', '2022-11-01', 'registered', true],
            ['كريم الرواتب', 'Accounts Receivable Officer', '1250.000', '2020-08-12', 'registered', true],
            ['رنا التقارير', 'Compliance Coordinator', '1550.000', '2019-04-20', 'registered', true],
            ['يزن التدقيق', 'Internal Auditor', '3100.000', '2016-07-07', 'registered', true],
            ['سارة العمليات', 'Operations Manager', '3500.000', '2013-10-11', 'registered', true],
            ['محمد المخزون', 'Warehouse Supervisor', '980.000', '2023-01-08', 'registered', true],
            ['تالا المشتريات', 'Procurement Officer', '1450.000', '2021-05-16', 'registered', true],
            ['خالد المبيعات', 'Sales Representative', '850.000', '2024-02-01', 'registered', false],
            ['ريم الخدمات', 'Customer Service Officer', '780.000', '2024-07-14', 'registered', false],
            ['مازن التقنية', 'IT Administrator', '1950.000', '2020-12-03', 'registered', true],
            ['هبة الجودة', 'Quality Officer', '1100.000', '2022-03-21', 'registered', false],
            ['جود التنسيق', 'Office Coordinator', '720.000', '2025-01-05', 'registered', false],
            ['أنس الدعم', 'Support Specialist', '900.000', '2024-10-09', 'registered', false],
            ['لمى المشاريع', 'Project Manager', '2800.000', '2018-09-17', 'registered', true],
            ['فارس الاستشارات', 'Senior Consultant', '6500.000', '2011-11-23', 'registered', true],
            ['ميس الموارد', 'HR Assistant', '760.000', '2025-06-01', 'registered', false],
            ['رامي الخدمات', 'Service Technician', '690.000', '2025-08-18', 'registered', false],
            ['بيان التدريب', 'Junior Accountant', '620.000', '2026-02-02', 'registered', true],
            ['زين المتابعة', 'Collections Officer', '800.000', '2026-05-10', 'registered', false],
            ['موظف ضمان ناقص', 'New Joiner — Demo Alert', '550.000', '2026-08-15', 'missing', true],
            ['موظف تاريخ ناقص', 'New Joiner — Demo Alert', '575.000', '2026-09-01', 'date_missing', true],
        ];

        $employees = [];
        foreach ($rows as $index => [$name, $title, $salary, $hireDate, $sscState, $portal]) {
            $sequence = $index + 1;
            $user = null;
            if ($portal) {
                $user = User::updateOrCreate(
                    ['email' => sprintf('employee%02d.demo@almaher.localhost', $sequence)],
                    [
                        'name' => $name,
                        'password' => self::EMPLOYEE_PASSWORD,
                        'role' => 'employee',
                        'locale' => $sequence % 3 === 0 ? 'en' : 'ar',
                        'active' => true,
                    ]
                );
            }

            $sscNumber = $sscState === 'missing' ? null : sprintf('DEMO-SSC-%04d', 1000 + $sequence);
            $sscDate = $sscState === 'registered'
                ? Carbon::parse($hireDate)->addDays(3)->toDateString()
                : null;
            $status = in_array($sequence, [14, 21], true)
                ? 'on_leave'
                : (in_array($sequence, [24, 25], true) ? 'inactive' : 'active');

            $employee = Employee::updateOrCreate(
                ['national_id' => sprintf('DEMO-NID-%04d', $sequence)],
                [
                    'user_id' => $user?->id,
                    'name' => $name,
                    'ssc_number' => $sscNumber,
                    'ssc_enrollment_date' => $sscDate,
                    'hire_date' => $hireDate,
                    'job_title' => $title,
                    'salary' => $salary,
                    'bank_iban' => sprintf('JO00DEMO%022d', $sequence),
                    'status' => $status,
                ]
            );

            $employees[$employee->national_id] = $employee;
        }

        return $employees;
    }

    /** @param array<string, Employee> $employees */
    private function seedLeave(array $employees, LeaveService $service, int $approverId): void
    {
        $specs = [
            ['0001', 'annual', '2026-02-08', '2026-02-10', false, 'إجازة عائلية تجريبية', 'approved'],
            ['0002', 'sick', '2026-03-15', '2026-03-16', false, 'مراجعة طبية تجريبية', 'approved'],
            ['0003', 'annual', '2026-04-05', '2026-04-09', false, 'إجازة سنوية تجريبية', 'approved'],
            ['0006', 'sick', '2026-06-07', '2026-06-09', true, 'إجازة مرضية تجريبية', 'approved'],
            ['0014', 'annual', '2026-09-01', '2026-09-07', false, 'إجازة حالية لعرض حالة على إجازة', 'approved'],
            ['0021', 'sick', '2026-09-01', '2026-09-06', false, 'إجازة مرضية حالية تجريبية', 'approved'],
            ['0004', 'annual', '2026-09-13', '2026-09-15', false, 'طلب بانتظار موافقة الموارد البشرية', 'pending'],
            ['0007', 'sick', '2026-09-20', '2026-09-21', false, 'طلب مرضي بانتظار الموافقة', 'pending'],
            ['0012', 'annual', '2026-10-04', '2026-10-08', false, 'إجازة مخططة بانتظار الموافقة', 'pending'],
            ['0018', 'annual', '2026-11-01', '2026-11-03', false, 'طلب إجازة تجريبي', 'pending'],
            ['0008', 'annual', '2026-05-10', '2026-05-11', false, 'تعارض مع إغلاق شهري', 'rejected'],
            ['0013', 'sick', '2026-07-12', '2026-07-13', false, 'مستندات غير مكتملة', 'rejected'],
            ['0017', 'annual', '2026-08-02', '2026-08-03', false, 'ألغي بناء على طلب الموظف', 'cancelled'],
        ];

        foreach ($specs as [$suffix, $type, $start, $end, $hospitalized, $reason, $status]) {
            $employee = $employees['DEMO-NID-'.$suffix];
            $request = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('type', $type)
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->first();

            if (! $request) {
                $request = $service->createRequest(
                    $employee,
                    $type,
                    Carbon::parse($start),
                    Carbon::parse($end),
                    $hospitalized,
                    $reason
                );
            }

            if ($status === 'approved' && $request->status === 'pending') {
                $service->approve($request, $approverId);
            } elseif (in_array($status, ['rejected', 'cancelled'], true) && $request->status === 'pending') {
                $request->update(['status' => $status, 'approver_id' => $status === 'rejected' ? $approverId : null]);
            }
        }
    }

    /** @param array<string, Employee> $employees */
    private function seedOvertime(array $employees, OvertimeService $service, int $approverId): void
    {
        $approved = [];
        foreach (range(1, 18) as $sequence) {
            foreach ([5, 19] as $day) {
                $approved[] = [sprintf('%04d', $sequence), sprintf('2026-08-%02d', $day), $sequence % 4 === 0 ? '3.50' : '2.00', 'عمل إضافي لإغلاق شهر آب'];
            }
        }
        foreach ([1, 2, 3, 4] as $day) {
            $approved[] = ['0001', sprintf('2026-09-%02d', $day), '6.00', 'حالة تجريبية لعرض تنبيه سقف العمل الإضافي'];
        }

        foreach ($approved as [$suffix, $date, $hours, $notes]) {
            $this->upsertOvertime($employees['DEMO-NID-'.$suffix], $date, $hours, $notes, 'approved', $service, $approverId);
        }

        $pending = [
            ['0002', '2026-09-03', '2.50', 'إغلاق تسويات البنك'],
            ['0005', '2026-09-04', '3.00', 'متابعة طلبات العملاء'],
            ['0009', '2026-09-06', '2.00', 'مراجعة عينة تدقيق'],
            ['0015', '2026-09-07', '4.00', 'تحديث أنظمة الرواتب'],
            ['0023', '2026-09-08', '1.50', 'تدريب فريق المحاسبة'],
        ];
        foreach ($pending as [$suffix, $date, $hours, $notes]) {
            $this->upsertOvertime($employees['DEMO-NID-'.$suffix], $date, $hours, $notes, 'pending', $service, $approverId);
        }

        $rejected = [
            ['0006', '2026-08-11', '2.00', 'طلب مكرر'],
            ['0010', '2026-08-17', '4.00', 'لم يعتمد مدير القسم'],
            ['0016', '2026-08-22', '3.00', 'ساعات غير موثقة'],
        ];
        foreach ($rejected as [$suffix, $date, $hours, $notes]) {
            $this->upsertOvertime($employees['DEMO-NID-'.$suffix], $date, $hours, $notes, 'rejected', $service, $approverId);
        }
    }

    private function upsertOvertime(
        Employee $employee,
        string $date,
        string $hours,
        string $notes,
        string $status,
        OvertimeService $service,
        int $approverId
    ): void {
        $entry = OvertimeEntry::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $date, 'notes' => $notes],
            [
                'hours' => $hours,
                'rate_type' => $service->rateTypeForDate(Carbon::parse($date)),
                'status' => 'pending',
            ]
        );

        if ($status === 'approved' && $entry->status === 'pending') {
            $service->approve($entry, $approverId);
        } elseif ($status === 'rejected' && $entry->status === 'pending') {
            $entry->update(['status' => 'rejected', 'approver_id' => $approverId]);
        }
    }

    private function seedPayroll(int $userId): void
    {
        $service = app(PayrollRunService::class);
        $periods = [[12, 2025]];
        foreach (range(1, 8) as $month) {
            $periods[] = [$month, 2026];
        }

        foreach ($periods as [$month, $year]) {
            $run = PayrollRun::where('month', $month)->where('year', $year)->first();
            if (! $run) {
                $run = $service->createRun($month, $year, $userId);
            }
            if ($run->status === 'draft') {
                $run = $service->process($run);
            }

            $completedAt = Carbon::create($year, $month, 1)->endOfMonth()->setTime(18, 0);
            $run->forceFill([
                'created_at' => $completedAt->copy()->subDays(2),
                'updated_at' => $completedAt,
                'completed_at' => $completedAt,
            ])->saveQuietly();
            $run->payslips()->update(['created_at' => $completedAt, 'updated_at' => $completedAt]);
        }

        if (! PayrollRun::where('month', 9)->where('year', 2026)->exists()) {
            $service->createRun(9, 2026, $userId);
        }
    }

    /** @param array<string, Employee> $employees */
    private function seedTermination(array $employees, TerminationService $service, int $userId): void
    {
        $employee = $employees['DEMO-NID-0022'];
        if (! TerminationRecord::where('employee_id', $employee->id)->exists()) {
            $service->record(
                $employee,
                Carbon::parse('2026-08-20'),
                'انتهاء مشروع محدد المدة — حالة تجريبية',
                Carbon::parse('2026-07-28'),
                $userId
            );
        } else {
            $employee->update(['status' => 'terminated']);
            $employee->user?->update(['active' => false]);
        }
    }
}
