<?php

namespace App\Support;

final class Money
{
    /** Format minor units (piastres) as a decimal string, e.g. 125050 => "1,250.50". */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }

    /** Format with the localised currency label, e.g. "1,250.50 EGP". */
    public static function withCurrency(int $cents): string
    {
        return self::format($cents).' '.__('common.egp');
    }

    /** Parse a user-entered major-unit amount (EGP) into minor units. */
    public static function toMinor(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $normalized = str_replace([',', ' '], '', (string) $amount);

        return (int) round(((float) $normalized) * 100);
    }

    /** Convert minor units to a major-unit string suitable for form inputs. */
    public static function toMajor(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
