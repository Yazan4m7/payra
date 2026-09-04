<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Decimal
{
    public static function d(string|int $value): BigDecimal
    {
        return BigDecimal::of((string) $value);
    }

    public static function add(string|int $a, string|int $b, int $scale = 2): string
    {
        return (string) self::d($a)->plus(self::d($b))->toScale($scale, RoundingMode::HALF_UP);
    }

    public static function sub(string|int $a, string|int $b, int $scale = 2): string
    {
        return (string) self::d($a)->minus(self::d($b))->toScale($scale, RoundingMode::HALF_UP);
    }
}
