<?php

namespace App\Modules\Core\Support;

/**
 * UK VAT arithmetic. The rate lives in config as basis points (2000 = 20%)
 * so calculations stay integer based and rounding is explicit.
 */
final class Vat
{
    public static function rateBps(): int
    {
        return (int) config('erp.vat_rate_bps', 2000);
    }

    public static function rate(): float
    {
        return self::rateBps() / 10000;
    }

    /**
     * VAT payable on a net amount.
     */
    public static function onNet(int $netPence): int
    {
        return (int) round($netPence * self::rateBps() / 10000);
    }

    /**
     * Gross total for a net amount.
     */
    public static function grossFromNet(int $netPence): int
    {
        return $netPence + self::onNet($netPence);
    }

    /**
     * Net amount contained in a VAT-inclusive total.
     */
    public static function netFromGross(int $grossPence): int
    {
        return (int) round($grossPence * 10000 / (10000 + self::rateBps()));
    }

    /**
     * VAT contained in a VAT-inclusive total.
     */
    public static function fromGross(int $grossPence): int
    {
        return $grossPence - self::netFromGross($grossPence);
    }

    /**
     * @return array{net: int, vat: int, gross: int}
     */
    public static function breakdown(int $netPence): array
    {
        $vat = self::onNet($netPence);

        return [
            'net' => $netPence,
            'vat' => $vat,
            'gross' => $netPence + $vat,
        ];
    }
}
