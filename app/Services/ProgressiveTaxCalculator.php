<?php

namespace App\Services;

use App\Support\Money;
use Brick\Math\BigDecimal;
use RuntimeException;

final class ProgressiveTaxCalculator
{
    public function calculate(BigDecimal $taxable, array $brackets): BigDecimal
    {
        $tax = BigDecimal::zero();
        $previous = BigDecimal::zero();

        foreach ($brackets as $bracket) {
            if (! array_key_exists('rate_percent', $bracket)) {
                throw new RuntimeException('Each tax bracket requires rate_percent.');
            }

            $upTo = isset($bracket['up_to_jod']) && $bracket['up_to_jod'] !== null
                ? Money::d((string) $bracket['up_to_jod'])
                : null;

            $band = $upTo
                ? Money::min($taxable, $upTo)->minus($previous)
                : $taxable->minus($previous);

            if ($band->isGreaterThan(0)) {
                $tax = $tax->plus(Money::percent($band, (string) $bracket['rate_percent']));
            }

            if (! $upTo || $taxable->isLessThanOrEqualTo($upTo)) {
                break;
            }
            $previous = $upTo;
        }

        return $tax;
    }
}
