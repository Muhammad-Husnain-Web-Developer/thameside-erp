<?php

namespace App\Modules\Core\Support;

use InvalidArgumentException;

/**
 * Money is always handled as integer pence. This class is the only place
 * allowed to convert between pence and display formats, so no floating point
 * drift ever reaches the database.
 */
final class Money
{
    public const CURRENCY = 'GBP';

    /**
     * Format integer pence as a human readable amount, e.g. 123456 => £1,234.56.
     */
    public static function format(?int $pence, bool $withSymbol = true): string
    {
        $pence ??= 0;

        $sign = $pence < 0 ? '-' : '';
        $absolute = abs($pence);
        $pounds = intdiv($absolute, 100);
        $coppers = $absolute % 100;

        $amount = number_format($pounds).'.'.str_pad((string) $coppers, 2, '0', STR_PAD_LEFT);

        return $sign.($withSymbol ? config('erp.currency_symbol', '£') : '').$amount;
    }

    /**
     * Plain decimal string for exports and PDFs columns, e.g. 123456 => "1234.56".
     */
    public static function toDecimal(?int $pence): string
    {
        $pence ??= 0;

        return ($pence < 0 ? '-' : '').intdiv(abs($pence), 100).'.'.str_pad((string) (abs($pence) % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Parse user input ("£1,234.56", "1234.5", "12") into integer pence.
     */
    public static function parse(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value * 100;
        }

        $normalised = str_replace(['£', ',', ' ', "\u{a0}"], '', (string) $value);

        if (! is_numeric($normalised)) {
            throw new InvalidArgumentException("Unable to parse monetary value [{$value}].");
        }

        return (int) round((float) $normalised * 100);
    }

    /**
     * Floating point pounds for charting and inputs only - never for storage.
     */
    public static function toFloat(?int $pence): float
    {
        return round(($pence ?? 0) / 100, 2);
    }

    public static function sum(int ...$amounts): int
    {
        return array_sum($amounts);
    }

    /**
     * Split an amount across weights using the largest-remainder method so the
     * parts always add up to the original total exactly.
     *
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    public static function allocate(int $pence, array $weights): array
    {
        $totalWeight = array_sum($weights);

        if ($totalWeight <= 0) {
            throw new InvalidArgumentException('Total weight must be greater than zero.');
        }

        $allocated = [];
        $remainders = [];
        $running = 0;

        foreach ($weights as $key => $weight) {
            $exact = $pence * $weight;
            $share = intdiv($exact, $totalWeight);
            $allocated[$key] = $share;
            $remainders[$key] = $exact % $totalWeight;
            $running += $share;
        }

        arsort($remainders);

        foreach (array_keys($remainders) as $key) {
            if ($running >= $pence) {
                break;
            }

            $allocated[$key]++;
            $running++;
        }

        return $allocated;
    }
}
