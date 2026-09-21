<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Services\PayrollReportingService;
use Illuminate\Http\JsonResponse;

class PayrollReconciliationController extends Controller
{
    public function __invoke(PayrollRun $run, PayrollReportingService $reporting): JsonResponse
    {
        abort_unless(in_array($run->status, ['calculated','approved','completed'], true), 422, 'Payroll must be calculated first.');
        return response()->json([
            'run' => ['id'=>$run->id,'month'=>$run->month,'year'=>$run->year,'status'=>$run->status],
            'totals' => $reporting->totalsForRun($run),
            'reconciliation' => $reporting->reconcileRun($run),
        ]);
    }
}
