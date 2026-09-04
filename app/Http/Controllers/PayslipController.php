<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Services\PayrollReportingService;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function show(Payslip $payslip, PayrollReportingService $reporting)
    {
        $user = request()->user();
        if ($user->role === 'employee') {
            abort_unless($user->employee?->id === $payslip->employee_id, 403);
        } else {
            abort_unless($user->isHr(), 403);
        }

        $payslip->load('employee', 'payrollRun', 'payrollRun.complianceSetting');
        $ytd = $reporting->ytdForEmployee($payslip->employee, $payslip->payrollRun->year, $payslip->payrollRun->month);
        $filename = "payslip-{$payslip->employee_id}-{$payslip->payrollRun->year}-{$payslip->payrollRun->month}.pdf";

        return Pdf::loadView('pdf.payslip', ['payslip' => $payslip, 'ytd' => $ytd])
            ->setPaper('a4')
            ->download($filename);
    }
}
