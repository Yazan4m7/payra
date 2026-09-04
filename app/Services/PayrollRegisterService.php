<?php

namespace App\Services;

use App\Models\PayrollRun;

class PayrollRegisterService
{
    public function __construct(private PayrollReportingService $reporting) {}

    public function rows(PayrollRun $run): array
    {
        $run->loadMissing('payslips.employee');
        return $run->payslips->sortBy('employee_id')->map(fn ($payslip) => [
            'employee_id' => $payslip->employee_id,
            'employee_name' => $payslip->employee->name,
            'national_id' => $payslip->employee->national_id,
            'contract_salary' => $payslip->contract_salary,
            'base_salary' => $payslip->gross_salary,
            'proration_adjustment' => $payslip->proration_adjustment,
            'unpaid_absence_deduction' => $payslip->unpaid_absence_deduction,
            'overtime_pay' => $payslip->overtime_pay,
            'earnings_total' => $payslip->earnings_total,
            'adjustment_earnings_total' => $payslip->adjustment_earnings_total,
            'deductions_total' => $payslip->deductions_total,
            'adjustment_deductions_total' => $payslip->adjustment_deductions_total,
            'loan_deductions_total' => $payslip->loan_deductions_total,
            'ssc_employee' => $payslip->ssc_employee,
            'ssc_employer' => $payslip->ssc_employer,
            'income_tax' => $payslip->income_tax,
            'surcharge' => $payslip->surcharge,
            'net_salary' => $payslip->net_salary,
        ])->values()->all();
    }

    public function toCsv(PayrollRun $run): string
    {
        $rows = $this->rows($run);
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        $headers = array_keys($rows[0] ?? [
            'employee_id'=>null,'employee_name'=>null,'national_id'=>null,'contract_salary'=>null,
            'base_salary'=>null,'proration_adjustment'=>null,'unpaid_absence_deduction'=>null,
            'overtime_pay'=>null,'earnings_total'=>null,'adjustment_earnings_total'=>null,
            'deductions_total'=>null,'adjustment_deductions_total'=>null,'loan_deductions_total'=>null,
            'ssc_employee'=>null,'ssc_employer'=>null,'income_tax'=>null,'surcharge'=>null,'net_salary'=>null,
        ]);
        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            $row['employee_name'] = $this->spreadsheetSafe((string) $row['employee_name']);
            $row['national_id'] = $this->spreadsheetSafe((string) $row['national_id']);
            fputcsv($stream, array_values($row));
        }
        $totals = $this->reporting->totalsForRun($run);
        fputcsv($stream, ['TOTAL','','','', $totals['gross_salary'],'', $totals['unpaid_absence_deduction'], $totals['overtime_pay'], $totals['earnings_total'], $totals['adjustment_earnings_total'], $totals['deductions_total'], $totals['adjustment_deductions_total'], $totals['loan_deductions_total'], $totals['ssc_employee'], $totals['ssc_employer'], $totals['income_tax'], $totals['surcharge'], $totals['net_salary']]);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
