<?php

namespace App\Services;

use App\Models\ComplianceSetting;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeEarning;
use App\Models\OvertimeEntry;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use RuntimeException;

class PayrollEngine
{
    public function __construct(
        private ComplianceSettingsService $compliance,
        private SscCalculator $ssc,
        private ProgressiveTaxCalculator $taxCalculator,
    ) {}

    public function calculate(Employee $employee, int $month, int $year, ?ComplianceSetting $setting = null): array
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $record = $setting ?: $this->compliance->forDate($periodEnd);
        $settings = $record->settings;
        $this->compliance->require($settings, [
            'ssc_employee_percent', 'ssc_employer_percent', 'ssc_enrollment_cutoff_date', 'ssc_ceiling_pre_cutoff_jod', 'ssc_ceiling_post_cutoff_jod',
            'income_tax_brackets', 'personal_exemption_annual_jod', 'high_earner_surcharge_threshold_annual_jod', 'high_earner_surcharge_percent',
            'overtime_multiplier_standard', 'overtime_multiplier_rest_holiday', 'monthly_hours_divisor',
        ]);

        $baseSalary = Money::d($employee->salary);
        $earningCalculation = $this->earningsForPeriod($employee, $periodStart, $periodEnd);
        $deductionCalculation = $this->deductionsForPeriod($employee, $periodStart, $periodEnd);
        $earnings = $earningCalculation['total'];
        $taxableEarnings = $earningCalculation['taxable'];
        $sscEarnings = $earningCalculation['ssc_applicable'];
        $deductions = $deductionCalculation['total'];

        $sscBasePay = $baseSalary
            ->plus($sscEarnings)
            ->minus($deductionCalculation['ssc_base_reduction']);
        if ($sscBasePay->isLessThan(0)) {
            $sscBasePay = BigDecimal::zero();
        }

        $ssc = $this->ssc->calculate($sscBasePay, $employee->ssc_enrollment_date, $settings);
        $overtimeCalculation = $this->overtimePay($employee, $month, $year, $baseSalary);
        $overtime = $overtimeCalculation['total'];

        $monthlyTaxable = $baseSalary
            ->plus($overtime)
            ->plus($taxableEarnings)
            ->minus($ssc['employee'])
            ->minus($deductionCalculation['taxable_reduction']);
        if ($monthlyTaxable->isLessThan(0)) {
            $monthlyTaxable = BigDecimal::zero();
        }

        $annualIncome = $baseSalary
            ->plus($overtime)
            ->plus($taxableEarnings)
            ->multipliedBy('12');
        $annualTaxable = $monthlyTaxable
            ->multipliedBy('12')
            ->minus((string) $settings['personal_exemption_annual_jod']);
        if ($annualTaxable->isLessThan(0)) {
            $annualTaxable = BigDecimal::zero();
        }

        $annualTax = $this->taxCalculator->calculate($annualTaxable, $settings['income_tax_brackets']);
        $annualSurcharge = BigDecimal::zero();
        $threshold = Money::d((string) $settings['high_earner_surcharge_threshold_annual_jod']);
        if ($annualIncome->isGreaterThan($threshold)) {
            $annualSurcharge = Money::percent(
                $annualIncome->minus($threshold),
                (string) $settings['high_earner_surcharge_percent']
            );
        }

        $incomeTax = $annualTax->dividedBy('12', 12, RoundingMode::HALF_UP);
        $monthlySurcharge = $annualSurcharge->dividedBy('12', 12, RoundingMode::HALF_UP);
        $net = $baseSalary
            ->plus($overtime)
            ->plus($earnings)
            ->minus($deductions)
            ->minus($ssc['employee'])
            ->minus($incomeTax)
            ->minus($monthlySurcharge);

        return [
            'gross_salary' => Money::round($baseSalary),
            'overtime_pay' => Money::round($overtime),
            'earnings_total' => Money::round($earnings),
            'deductions_total' => Money::round($deductions),
            'ssc_employee' => Money::round($ssc['employee']),
            'ssc_employer' => Money::round($ssc['employer']),
            'income_tax' => Money::round($incomeTax),
            'surcharge' => Money::round($monthlySurcharge),
            'net_salary' => Money::round($net),
            'compliance_setting_id' => $record->id,
            'snapshot' => [
                'setting_version' => $record->version_label,
                'effective_date' => $record->effective_date->toDateString(),
                'settings' => $settings,
                'base_salary' => Money::round($baseSalary),
                'earnings_total' => Money::round($earnings),
                'earnings_details' => $earningCalculation['details'],
                'deductions_total' => Money::round($deductions),
                'deduction_details' => $deductionCalculation['details'],
                'ssc_ceiling_used' => Money::round($ssc['ceiling']),
                'ssc_base' => Money::round($ssc['base']),
                'pre_cutoff_enrollment' => $ssc['pre_cutoff'],
                'annual_income' => Money::round($annualIncome),
                'annual_taxable' => Money::round($annualTaxable),
                'overtime_details' => $overtimeCalculation['details'],
            ],
        ];
    }

    private function earningsForPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rows = EmployeeEarning::query()
            ->where('employee_id', $employee->id)
            ->applicableTo($periodStart, $periodEnd)
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        $total = BigDecimal::zero();
        $taxable = BigDecimal::zero();
        $sscApplicable = BigDecimal::zero();
        $details = [];

        foreach ($rows as $earning) {
            $amount = Money::d($earning->amount);
            $total = $total->plus($amount);

            if ($earning->taxable) {
                $taxable = $taxable->plus($amount);
            }
            if ($earning->ssc_applicable) {
                $sscApplicable = $sscApplicable->plus($amount);
            }

            $details[] = [
                'earning_id' => $earning->id,
                'category' => $earning->category,
                'name' => $earning->name,
                'amount_jod' => Money::round($amount),
                'taxable' => $earning->taxable,
                'ssc_applicable' => $earning->ssc_applicable,
                'recurring' => $earning->recurring,
            ];
        }

        return [
            'total' => $total,
            'taxable' => $taxable,
            'ssc_applicable' => $sscApplicable,
            'details' => $details,
        ];
    }

    private function deductionsForPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rows = EmployeeDeduction::query()
            ->where('employee_id', $employee->id)
            ->applicableTo($periodStart, $periodEnd)
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        $total = BigDecimal::zero();
        $taxableReduction = BigDecimal::zero();
        $sscBaseReduction = BigDecimal::zero();
        $details = [];

        foreach ($rows as $deduction) {
            $amount = Money::d($deduction->amount);
            $total = $total->plus($amount);

            if ($deduction->reduces_taxable_income) {
                $taxableReduction = $taxableReduction->plus($amount);
            }
            if ($deduction->reduces_ssc_base) {
                $sscBaseReduction = $sscBaseReduction->plus($amount);
            }

            $details[] = [
                'deduction_id' => $deduction->id,
                'category' => $deduction->category,
                'name' => $deduction->name,
                'amount_jod' => Money::round($amount),
                'reduces_taxable_income' => $deduction->reduces_taxable_income,
                'reduces_ssc_base' => $deduction->reduces_ssc_base,
                'recurring' => $deduction->recurring,
            ];
        }

        return [
            'total' => $total,
            'taxable_reduction' => $taxableReduction,
            'ssc_base_reduction' => $sscBaseReduction,
            'details' => $details,
        ];
    }

    private function overtimePay(Employee $employee, int $month, int $year, BigDecimal $gross): array
    {
        $entries = OvertimeEntry::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $total = BigDecimal::zero();
        $details = [];
        $settingsCache = [];

        foreach ($entries as $entry) {
            $dateKey = $entry->date->toDateString();
            if (! isset($settingsCache[$dateKey])) {
                $record = $this->compliance->forDate($entry->date);
                $this->compliance->require($record->settings, [
                    'monthly_hours_divisor', 'overtime_multiplier_standard', 'overtime_multiplier_rest_holiday',
                ]);
                $settingsCache[$dateKey] = $record;
            }

            $record = $settingsCache[$dateKey];
            $entrySettings = $record->settings;
            $divisor = Money::d((string) $entrySettings['monthly_hours_divisor']);
            if ($divisor->isZero() || $divisor->isNegative()) {
                throw new RuntimeException('monthly_hours_divisor must be greater than zero.');
            }

            $hourly = $gross->dividedBy($divisor, 12, RoundingMode::HALF_UP);
            $key = $entry->rate_type === 'rest_holiday'
                ? 'overtime_multiplier_rest_holiday'
                : 'overtime_multiplier_standard';
            $multiplier = (string) $entrySettings[$key];
            $pay = $hourly->multipliedBy((string) $entry->hours)->multipliedBy($multiplier);
            $total = $total->plus($pay);

            $details[] = [
                'entry_id' => $entry->id,
                'date' => $dateKey,
                'hours' => (string) $entry->hours,
                'rate_type' => $entry->rate_type,
                'multiplier' => $multiplier,
                'monthly_hours_divisor' => (string) $entrySettings['monthly_hours_divisor'],
                'settings_version' => $record->version_label,
                'amount_jod' => Money::round($pay),
            ];
        }

        return ['total' => $total, 'details' => $details];
    }
}
