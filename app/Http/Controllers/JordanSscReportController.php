<?php
namespace App\Http\Controllers;
use App\Models\PayrollRun; use App\Services\JordanFilingReportService; use Illuminate\Http\Response;
class JordanSscReportController extends Controller { public function __invoke(PayrollRun $run,JordanFilingReportService $s):Response{return response($s->sscCsv($run),200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="ssc-contributions-'.$run->year.'-'.$run->month.'.csv"']);} }
