<?php

use App\Http\Controllers\BankExportController;
use App\Http\Controllers\BiometricEventController;
use App\Http\Controllers\EmployeeDocumentDownloadController;
use App\Http\Controllers\GlExportController;
use App\Http\Controllers\JordanSscReportController;
use App\Http\Controllers\JordanTaxA2Controller;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PayrollComparisonController;
use App\Http\Controllers\PayrollReconciliationController;
use App\Http\Controllers\PayrollRegisterController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\TenantAuthController;
use App\Livewire\Absences\Index as AbsencesIndex;
use App\Livewire\Accounting\Mapping as AccountingMapping;
use App\Livewire\Adjustments\Index as AdjustmentsIndex;
use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Biometric\Devices as BiometricDevices;
use App\Livewire\Company\Settings as CompanySettings;
use App\Livewire\Compliance\Settings as ComplianceSettings;
use App\Livewire\Contracts\Index as ContractsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Deductions\Index as DeductionsIndex;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Earnings\Index as EarningsIndex;
use App\Livewire\Employees\BulkActions as EmployeeBulkActions;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Holidays\Index as HolidaysIndex;
use App\Livewire\Imports\Center as ImportCenter;
use App\Livewire\Leave\Requests as LeaveRequests;
use App\Livewire\Loans\Index as LoansIndex;
use App\Livewire\Notifications\Center as NotificationCenter;
use App\Livewire\Organization\Structure as OrganizationStructure;
use App\Livewire\Overtime\Entries as OvertimeEntries;
use App\Livewire\Payroll\Runs as PayrollRuns;
use App\Livewire\Performance\Index as PerformanceIndex;
use App\Livewire\Performance\MyReviews as MyPerformanceReviews;
use App\Livewire\Permissions\Index as PermissionsIndex;
use App\Livewire\Recruiting\Index as RecruitingIndex;
use App\Livewire\Salaries\Index as SalariesIndex;
use App\Livewire\SelfService\Home as SelfServiceHome;
use App\Livewire\Terminations\Index as TerminationsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'throttle:120,1',
])->post('/api/biometric/{device}/events', BiometricEventController::class)->name('biometric.events');

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    ScopeSessions::class,
    'locale',
])->group(function () {
    Route::get('/locale/{locale}', LocaleController::class)->name('locale');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [TenantAuthController::class, 'create'])->name('login');
        Route::post('/login', [TenantAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [TenantAuthController::class, 'destroy'])->name('logout');
        Route::get('/notifications', NotificationCenter::class)->name('notifications.center');
        Route::get('/self-service', SelfServiceHome::class)->name('self-service');
        Route::get('/performance/my', MyPerformanceReviews::class)->name('performance.my');
        Route::get('/payslips/{payslip}/pdf', [PayslipController::class, 'show'])->name('payslips.pdf');
        Route::get('/documents/{document}/download', EmployeeDocumentDownloadController::class)->name('documents.download');

        Route::middleware('tenant.role:company_admin,hr')->group(function () {
            Route::get('/', Dashboard::class)->name('dashboard');
            Route::get('/employees', EmployeesIndex::class)->middleware('can:employees.manage')->name('employees.index');
            Route::get('/employees/bulk', EmployeeBulkActions::class)->middleware('can:employees.manage')->name('employees.bulk');
            Route::get('/organization', OrganizationStructure::class)->middleware('can:organization.manage')->name('organization.structure');
            Route::get('/contracts', ContractsIndex::class)->middleware('can:contracts.manage')->name('contracts.index');
            Route::get('/documents', DocumentsIndex::class)->middleware('can:documents.manage')->name('documents.index');
            Route::get('/imports', ImportCenter::class)->middleware('can:imports.manage')->name('imports.center');
            Route::get('/recruiting', RecruitingIndex::class)->middleware('can:recruiting.manage')->name('recruiting.index');
            Route::get('/performance', PerformanceIndex::class)->middleware('can:performance.manage')->name('performance.index');
            Route::get('/accounting', AccountingMapping::class)->middleware('can:accounting.export')->name('accounting.mapping');
            Route::get('/audit', AuditIndex::class)->middleware('can:audit.view')->name('audit.index');
            Route::get('/attendance', AttendanceIndex::class)->middleware('can:attendance.manage')->name('attendance.index');
            Route::get('/biometric', BiometricDevices::class)->middleware('can:biometric.manage')->name('biometric.devices');
            Route::get('/salary-history', SalariesIndex::class)->middleware('can:employees.manage')->name('salaries.index');
            Route::get('/earnings', EarningsIndex::class)->middleware('can:employees.manage')->name('earnings.index');
            Route::get('/deductions', DeductionsIndex::class)->middleware('can:employees.manage')->name('deductions.index');
            Route::get('/loans', LoansIndex::class)->middleware('can:employees.manage')->name('loans.index');
            Route::get('/absences', AbsencesIndex::class)->middleware('can:attendance.manage')->name('absences.index');
            Route::get('/adjustments', AdjustmentsIndex::class)->middleware('can:adjustments.manage')->name('adjustments.index');
            Route::get('/payroll', PayrollRuns::class)->name('payroll.runs');
            Route::get('/leave', LeaveRequests::class)->middleware('can:attendance.manage')->name('leave.requests');
            Route::get('/overtime', OvertimeEntries::class)->middleware('can:attendance.manage')->name('overtime.entries');
            Route::get('/terminations', TerminationsIndex::class)->middleware('can:employees.manage')->name('terminations.index');
            Route::get('/holidays', HolidaysIndex::class)->middleware('can:attendance.manage')->name('holidays.index');
            Route::get('/payroll/{run}/bank-export', BankExportController::class)->middleware('can:payroll.export')->name('payroll.bank-export');
            Route::get('/payroll/{run}/gl-export.csv', GlExportController::class)->middleware('can:accounting.export')->name('payroll.gl-export');
            Route::get('/payroll/{run}/register.csv', PayrollRegisterController::class)->middleware('can:payroll.export')->name('payroll.register');
            Route::get('/payroll/{run}/reconciliation', PayrollReconciliationController::class)->middleware('can:payroll.export')->name('payroll.reconciliation');
            Route::get('/payroll/{run}/comparison', PayrollComparisonController::class)->middleware('can:payroll.export')->name('payroll.comparison');
            Route::get('/payroll/{run}/istd-a2.csv', JordanTaxA2Controller::class)->middleware('can:payroll.export')->name('payroll.istd-a2');
            Route::get('/payroll/{run}/ssc-report.csv', JordanSscReportController::class)->middleware('can:payroll.export')->name('payroll.ssc-report');
            Route::get('/compliance-settings', ComplianceSettings::class)->middleware('can:compliance.manage')->name('compliance.settings');
            Route::get('/users', UsersIndex::class)->middleware('tenant.role:company_admin')->name('users.index');
            Route::get('/permissions', PermissionsIndex::class)->middleware('tenant.role:company_admin')->name('permissions.index');
            Route::get('/company-settings', CompanySettings::class)->middleware('tenant.role:company_admin')->name('company.settings');
        });
    });
});
