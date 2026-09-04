<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankExportController extends Controller
{
    public function __invoke(PayrollRun $run): StreamedResponse
    {
        abort_unless($run->status === 'completed', 422, 'Payroll run must be completed.');
        $run->load('payslips.employee');
        $missing = $run->payslips->filter(fn ($p) => blank($p->employee->bank_iban));
        abort_if($missing->isNotEmpty(), 422, 'Bank export blocked: one or more employees have no IBAN.');

        return response()->streamDownload(function () use ($run) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Arabic names in spreadsheet tools.
            fputcsv($handle, ['employee_name', 'iban', 'net_amount_jod']);
            foreach ($run->payslips as $payslip) {
                fputcsv($handle, [
                    $this->spreadsheetSafe($payslip->employee->name),
                    $payslip->employee->bank_iban,
                    $payslip->net_salary,
                ]);
            }
            fclose($handle);
        }, "bank-transfer-{$run->year}-{$run->month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
