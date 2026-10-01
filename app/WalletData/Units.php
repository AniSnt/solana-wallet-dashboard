<?php

namespace App\WalletData;

use Brick\Math\BigDecimal;

final class Units
{
    /** Divides a raw integer string by 10^decimals without ever using float. */
    public static function toDecimal(string $raw, int $decimals): string
    {
        return (string) BigDecimal::of($raw)
            ->withPointMovedLeft($decimals)
            ->strippedOfTrailingZeros();
    }

    public static function sol(string $lamports): string
    {
        return self::toDecimal($lamports, 9);
    }
}
