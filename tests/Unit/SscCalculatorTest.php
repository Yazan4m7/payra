<?php

namespace Tests\Unit;

use App\Services\SscCalculator;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SscCalculatorTest extends TestCase
{
    private array $settings = [
        'ssc_employee_percent' => '5',
        'ssc_employer_percent' => '10',
        'ssc_enrollment_cutoff_date' => '2020-01-01',
        'ssc_ceiling_pre_cutoff_jod' => '1000.000',
        'ssc_ceiling_post_cutoff_jod' => '500.000',
    ];

    public function test_pre_cutoff_employee_uses_pre_cutoff_ceiling(): void
    {
        $result = (new SscCalculator())->calculate(BigDecimal::of('1500'), Carbon::parse('2019-12-31'), $this->settings);
        $this->assertTrue($result['pre_cutoff']);
        $this->assertSame('1000.000', (string) $result['base']->toScale(3));
        $this->assertSame('50.000', (string) $result['employee']->toScale(3));
    }

    public function test_post_cutoff_employee_uses_post_cutoff_ceiling(): void
    {
        $result = (new SscCalculator())->calculate(BigDecimal::of('1500'), Carbon::parse('2020-01-01'), $this->settings);
        $this->assertFalse($result['pre_cutoff']);
        $this->assertSame('500.000', (string) $result['base']->toScale(3));
        $this->assertSame('50.000', (string) $result['employer']->toScale(3));
    }
}
