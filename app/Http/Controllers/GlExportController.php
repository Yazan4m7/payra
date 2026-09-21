<?php
namespace App\Http\Controllers;
use App\Models\PayrollRun; use App\Services\GlExportService; use Illuminate\Http\Response;
class GlExportController extends Controller { public function __invoke(PayrollRun $run,GlExportService $service):Response{return response($service->csv($run),200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="payroll-gl-'.$run->year.'-'.$run->month.'.csv"','X-Content-Type-Options'=>'nosniff']);} }
