<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_currency_rounds_to_three_jod_decimals_without_float_math(): void
    {
        $result = Money::d('0.1')->plus('0.2');
        $this->assertSame('0.300', Money::round($result));
    }

    public function test_percentage_uses_decimal_arithmetic(): void
    {
        $this->assertSame('7.250', Money::round(Money::percent(Money::d('100.000'), '7.25')));
    }

    public function test_float_currency_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::d(0.1);
    }
}
