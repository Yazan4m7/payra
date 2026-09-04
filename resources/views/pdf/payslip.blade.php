<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #202833; }
    .header { width: 100%; margin-bottom: 14px; }
    .title { font-size: 20px; font-weight: bold; margin: 0; }
    .muted { color: #6b7280; }
    .box { border: 1px solid #d9dee7; padding: 10px; margin-bottom: 12px; border-radius: 6px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 7px; border-bottom: 1px solid #e5e7eb; }
    th { background: #f5f7fa; text-align: right; }
    .num { text-align: left; direction: ltr; font-variant-numeric: tabular-nums; }
    .net { font-size: 15px; font-weight: bold; }
    .ltr { direction: ltr; text-align: left; }
    .footer { margin-top: 16px; font-size: 9px; color: #6b7280; }
</style>
</head>
<body>
<table class="header"><tr><td style="border:0"><div class="title">قسيمة الراتب / Payslip</div><div class="muted">{{ tenant('name') }}</div></td><td style="border:0" class="ltr"><strong>{{ sprintf('%02d/%04d', $payslip->payrollRun->month, $payslip->payrollRun->year) }}</strong><br><span class="muted">JOD</span></td></tr></table>

<div class="box">
<table>
<tr><td><strong>الموظف / Employee</strong><br>{{ $payslip->employee->name }}</td><td><strong>المسمى / Job title</strong><br>{{ $payslip->employee->job_title ?: '—' }}</td></tr>
<tr><td><strong>الرقم الوطني / National ID</strong><br>{{ $payslip->employee->national_id }}</td><td><strong>رقم الضمان / SSC No.</strong><br>{{ $payslip->employee->ssc_number ?: '—' }}</td></tr>
</table>
</div>

<table>
<thead><tr><th>البند / Item</th><th class="num">JOD</th></tr></thead>
<tbody>
<tr><td>الراتب الأساسي / Base salary</td><td class="num">{{ $payslip->gross_salary }}</td></tr>
<tr><td>العمل الإضافي / Overtime</td><td class="num">+ {{ $payslip->overtime_pay }}</td></tr>
@foreach(($payslip->calculation_snapshot['earnings_details'] ?? []) as $earning)
<tr><td>{{ $earning['name'] }} / {{ ucfirst($earning['category']) }}</td><td class="num">+ {{ $earning['amount_jod'] }}</td></tr>
@endforeach
<tr><td>حصة الموظف للضمان / Employee SSC</td><td class="num">- {{ $payslip->ssc_employee }}</td></tr>
<tr><td>ضريبة الدخل / Income tax</td><td class="num">- {{ $payslip->income_tax }}</td></tr>
<tr><td>الرسم الإضافي / Surcharge</td><td class="num">- {{ $payslip->surcharge }}</td></tr>
<tr><td class="net">الصافي / Net pay</td><td class="num net">{{ $payslip->net_salary }}</td></tr>
</tbody>
</table>

<div class="box" style="margin-top:14px">
    <strong>معلومات صاحب العمل / Employer information</strong><br>
    حصة صاحب العمل للضمان / Employer SSC contribution: <span class="ltr">{{ $payslip->ssc_employer }} JOD</span><br>
    رقم منشأة الضمان / SSC establishment no.: {{ tenant('ssc_establishment_number') ?: '—' }}
</div>

<div class="footer">
    نسخة إعدادات الامتثال / Compliance settings version: {{ $payslip->payrollRun->complianceSetting?->version_label ?: ($payslip->calculation_snapshot['setting_version'] ?? '—') }}.
    This document is generated from the finalized payroll calculation snapshot for the stated period.
</div>
</body>
</html>
