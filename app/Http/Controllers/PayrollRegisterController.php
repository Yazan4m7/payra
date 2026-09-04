<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Services\PayrollRegisterService;
use Symfony\Component\HttpFoundation\Response;

class PayrollRegisterController extends Controller
{
    public function __invoke(PayrollRun $run, PayrollRegisterService $register): Response
    {
        abort_unless(in_array($run->status, ['calculated','approved','completed'], true), 422, 'Payroll must be calculated first.');
        $csv = $register->toCsv($run);
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payroll-register-'.$run->year.'-'.$run->month.'.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
