<?php
namespace App\Http\Controllers;
use App\Models\PayrollRun; use App\Services\JordanFilingReportService; use Illuminate\Http\Response;
class JordanTaxA2Controller extends Controller { public function __invoke(PayrollRun $run,JordanFilingReportService $s):Response{return response($s->taxA2Csv($run),200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="istd-a2-'.$run->year.'-'.$run->month.'.csv"']);} }
