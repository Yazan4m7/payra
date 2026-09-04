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
        private LoanService $loans,
        private AbsenceService $absences,
        private SalaryProrationService $proration,
        private SalaryHistoryService $salaries,
        private PayrollAdjustmentService $adjustments,
    ) {}

    public function calculate(Employee $employee, int $month, int $year, ?ComplianceSetting $setting = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $record = $setting ?: $this->compliance->forDate($end);
        $settings = $record->settings;
        $this->compliance->require($settings, [
            'ssc_employee_percent', 'ssc_employer_percent', 'ssc_enrollment_cutoff_date',
            'ssc_ceiling_pre_cutoff_jod', 'ssc_ceiling_post_cutoff_jod', 'income_tax_brackets',
            'personal_exemption_annual_jod', 'high_earner_surcharge_threshold_annual_jod',
            'high_earner_surcharge_percent', 'overtime_multiplier_standard',
            'overtime_multiplier_rest_holiday', 'monthly_hours_divisor', 'salary_daily_divisor',
        ]);

        $proration = $this->proration->calculate($employee, $start, $end, $settings);
        $contractSalary = Money::d($proration['contract_salary']);
        $scheduledBase = Money::d($proration['payable_salary']);
        $earnings = $this->earningsForPeriod($employee, $start, $end);
        $deductions = $this->deductionsForPeriod($employee, $start, $end);
        $adjustments = $this->adjustments->forPayroll($employee, $month, $year);
        $loans = $this->loans->dueForPeriod($employee, $start, $end);
        $absence = $this->absences->deductionForPeriod($employee, $start, $end, $settings);

        $earnedBase = $scheduledBase->minus($absence['total']);
        if ($earnedBase->isLessThan(0)) $earnedBase = BigDecimal::zero();

        $sscBase = $earnedBase
            ->plus($earnings['ssc_applicable'])
            ->plus($adjustments['ssc_earnings'])
            ->minus($deductions['ssc_base_reduction'])
            ->minus($adjustments['ssc_base_reduction']);
        if ($sscBase->isLessThan(0)) $sscBase = BigDecimal::zero();
        $ssc = $this->ssc->calculate($sscBase, $employee->ssc_enrollment_date, $settings);

        $overtimeCalculation = $this->overtimePay($employee, $month, $year);
        $overtime = $overtimeCalculation['total'];
        $monthlyTaxable = $earnedBase
            ->plus($overtime)
            ->plus($earnings['taxable'])
            ->plus($adjustments['taxable_earnings'])
            ->minus($ssc['employee'])
            ->minus($deductions['taxable_reduction'])
            ->minus($adjustments['taxable_reduction']);
        if ($monthlyTaxable->isLessThan(0)) $monthlyTaxable = BigDecimal::zero();

        $annualIncome = $earnedBase
            ->plus($overtime)
            ->plus($earnings['taxable'])
            ->plus($adjustments['taxable_earnings'])
            ->multipliedBy('12');
        $annualTaxable = $monthlyTaxable->multipliedBy('12')
            ->minus((string) $settings['personal_exemption_annual_jod']);
        if ($annualTaxable->isLessThan(0)) $annualTaxable = BigDecimal::zero();

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
        $surcharge = $annualSurcharge->dividedBy('12', 12, RoundingMode::HALF_UP);

        $net = $earnedBase
            ->plus($overtime)
            ->plus($earnings['total'])
            ->plus($adjustments['earning_total'])
            ->minus($deductions['total'])
            ->minus($adjustments['deduction_total'])
            ->minus($loans['total'])
            ->minus($ssc['employee'])
            ->minus($incomeTax)
            ->minus($surcharge);

        return [
            'contract_salary' => $proration['contract_salary'],
            'gross_salary' => $proration['payable_salary'],
            'proration_adjustment' => $proration['proration_adjustment'],
            'overtime_pay' => Money::round($overtime),
            'earnings_total' => Money::round($earnings['total']),
            'adjustment_earnings_total' => Money::round($adjustments['earning_total']),
            'deductions_total' => Money::round($deductions['total']),
            'adjustment_deductions_total' => Money::round($adjustments['deduction_total']),
            'loan_deductions_total' => Money::round($loans['total']),
            'unpaid_absence_deduction' => Money::round($absence['total']),
            'ssc_employee' => Money::round($ssc['employee']),
            'ssc_employer' => Money::round($ssc['employer']),
            'income_tax' => Money::round($incomeTax),
            'surcharge' => Money::round($surcharge),
            'net_salary' => Money::round($net),
            'compliance_setting_id' => $record->id,
            'loan_repayments' => $loans['details'],
            'adjustments' => $adjustments['details'],
            'snapshot' => [
                'setting_version' => $record->version_label,
                'effective_date' => $record->effective_date->toDateString(),
                'settings' => $settings,
                'salary_proration' => $proration,
                'earned_base_salary' => Money::round($earnedBase),
                'unpaid_absence_deduction' => Money::round($absence['total']),
                'unpaid_absence_details' => $absence['details'],
                'earnings_total' => Money::round($earnings['total']),
                'earnings_details' => $earnings['details'],
                'adjustment_earnings_total' => Money::round($adjustments['earning_total']),
                'adjustment_deductions_total' => Money::round($adjustments['deduction_total']),
                'payroll_adjustments' => $adjustments['details'],
                'deductions_total' => Money::round($deductions['total']),
                'deduction_details' => $deductions['details'],
                'loan_deductions_total' => Money::round($loans['total']),
                'loan_repayment_details' => $loans['details'],
                'ssc_ceiling_used' => Money::round($ssc['ceiling']),
                'ssc_base' => Money::round($ssc['base']),
                'pre_cutoff_enrollment' => $ssc['pre_cutoff'],
                'annual_income' => Money::round($annualIncome),
                'annual_taxable' => Money::round($annualTaxable),
                'overtime_details' => $overtimeCalculation['details'],
            ],
        ];
    }

    private function earningsForPeriod(Employee $employee, Carbon $start, Carbon $end): array
    {
        $rows = EmployeeEarning::where('employee_id', $employee->id)->applicableTo($start, $end)->get();
        $total = BigDecimal::zero(); $taxable = BigDecimal::zero(); $ssc = BigDecimal::zero(); $details = [];
        foreach ($rows as $row) {
            $amount = Money::d($row->amount); $total = $total->plus($amount);
            if ($row->taxable) $taxable = $taxable->plus($amount);
            if ($row->ssc_applicable) $ssc = $ssc->plus($amount);
            $details[] = ['earning_id'=>$row->id,'category'=>$row->category,'name'=>$row->name,'amount_jod'=>Money::round($amount),'taxable'=>$row->taxable,'ssc_applicable'=>$row->ssc_applicable,'recurring'=>$row->recurring];
        }
        return ['total'=>$total,'taxable'=>$taxable,'ssc_applicable'=>$ssc,'details'=>$details];
    }

    private function deductionsForPeriod(Employee $employee, Carbon $start, Carbon $end): array
    {
        $rows = EmployeeDeduction::where('employee_id', $employee->id)->applicableTo($start, $end)->get();
        $total = BigDecimal::zero(); $taxable = BigDecimal::zero(); $ssc = BigDecimal::zero(); $details = [];
        foreach ($rows as $row) {
            $amount = Money::d($row->amount); $total = $total->plus($amount);
            if ($row->reduces_taxable_income) $taxable = $taxable->plus($amount);
            if ($row->reduces_ssc_base) $ssc = $ssc->plus($amount);
            $details[] = ['deduction_id'=>$row->id,'category'=>$row->category,'name'=>$row->name,'amount_jod'=>Money::round($amount),'reduces_taxable_income'=>$row->reduces_taxable_income,'reduces_ssc_base'=>$row->reduces_ssc_base,'recurring'=>$row->recurring];
        }
        return ['total'=>$total,'taxable_reduction'=>$taxable,'ssc_base_reduction'=>$ssc,'details'=>$details];
    }

    private function overtimePay(Employee $employee, int $month, int $year): array
    {
        $rows = OvertimeEntry::where('employee_id', $employee->id)->where('status', 'approved')
            ->whereYear('date', $year)->whereMonth('date', $month)->orderBy('date')->get();
        $total = BigDecimal::zero(); $details = []; $cache = [];
        foreach ($rows as $row) {
            $dateKey = $row->date->toDateString();
            if (! isset($cache[$dateKey])) {
                $compliance = $this->compliance->forDate($row->date);
                $this->compliance->require($compliance->settings, ['monthly_hours_divisor','overtime_multiplier_standard','overtime_multiplier_rest_holiday']);
                $cache[$dateKey] = $compliance;
            }
            $compliance = $cache[$dateKey]; $settings = $compliance->settings;
            $divisor = Money::d((string) $settings['monthly_hours_divisor']);
            if ($divisor->isLessThanOrEqualTo(0)) throw new RuntimeException('monthly_hours_divisor must be greater than zero.');
            $salary = $this->salaries->salaryAt($employee, $row->date);
            $hourly = $salary->dividedBy($divisor, 12, RoundingMode::HALF_UP);
            $multiplier = (string) $settings[$row->rate_type === 'rest_holiday' ? 'overtime_multiplier_rest_holiday' : 'overtime_multiplier_standard'];
            $pay = $hourly->multipliedBy((string) $row->hours)->multipliedBy($multiplier); $total = $total->plus($pay);
            $details[] = ['entry_id'=>$row->id,'date'=>$dateKey,'hours'=>(string)$row->hours,'rate_type'=>$row->rate_type,'monthly_salary_jod'=>Money::round($salary),'multiplier'=>$multiplier,'monthly_hours_divisor'=>(string)$settings['monthly_hours_divisor'],'settings_version'=>$compliance->version_label,'amount_jod'=>Money::round($pay)];
        }
        return ['total'=>$total,'details'=>$details];
    }
}
