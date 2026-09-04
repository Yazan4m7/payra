<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function show(Payslip $payslip)
    {
        $user = request()->user();
        if ($user->role === 'employee') {
            abort_unless($user->employee?->id === $payslip->employee_id, 403);
        } else {
            abort_unless($user->isHr(), 403);
        }

        $payslip->load('employee', 'payrollRun', 'payrollRun.complianceSetting');
        $filename = "payslip-{$payslip->employee_id}-{$payslip->payrollRun->year}-{$payslip->payrollRun->month}.pdf";

        return Pdf::loadView('pdf.payslip', ['payslip' => $payslip])
            ->setPaper('a4')
            ->download($filename);
    }
}
