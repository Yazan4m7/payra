<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class Money
{
    public const SCALE = 3;

    /** Currency values must never enter the calculation layer as PHP floats. */
    public static function d(mixed $value): BigDecimal
    {
        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Currency values must be passed as decimal strings or integers, never floats.');
        }

        return BigDecimal::of((string) $value);
    }

    public static function round(BigDecimal $value): string
    {
        return (string) $value->toScale(self::SCALE, RoundingMode::HALF_UP);
    }

    public static function min(BigDecimal $a, BigDecimal $b): BigDecimal
    {
        return $a->isLessThan($b) ? $a : $b;
    }

    public static function percent(BigDecimal $amount, string $percent): BigDecimal
    {
        return $amount
            ->multipliedBy(self::d($percent))
            ->dividedBy('100', 12, RoundingMode::HALF_UP);
    }
}
