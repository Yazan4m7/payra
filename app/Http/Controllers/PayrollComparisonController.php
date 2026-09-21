<?php
namespace App\Http\Controllers;
use App\Models\PayrollRun; use App\Services\PayrollComparisonService; use Illuminate\Http\JsonResponse;
class PayrollComparisonController extends Controller { public function __invoke(PayrollRun $run,PayrollComparisonService $service):JsonResponse{abort_unless(in_array($run->status,['calculated','approved','completed'],true),422,'Payroll must be calculated first.');abort_unless($run->run_type==='regular',422,'Comparison is for regular payroll runs.');return response()->json($service->compare($run));} }
