<?php

namespace Tests\Unit;

use App\Services\ProgressiveTaxCalculator;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;

class ProgressiveTaxCalculatorTest extends TestCase
{
    public function test_progressive_brackets_use_decimal_math_and_open_ended_final_band(): void
    {
        $calculator = new ProgressiveTaxCalculator();
        $tax = $calculator->calculate(BigDecimal::of('150.000'), [
            ['up_to_jod' => '100.000', 'rate_percent' => '10'],
            ['up_to_jod' => null, 'rate_percent' => '20'],
        ]);

        $this->assertSame('20.000', (string) $tax->toScale(3));
    }
}
