<?php

namespace App\Support;

final class Phone
{
    /**
     * Normalise an Egyptian mobile number to the local 01xxxxxxxxx format.
     * Returns null when the number cannot be interpreted as an Egyptian mobile.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        if (! preg_match((string) config('matjar.phone.pattern'), $digits)) {
            return null;
        }

        return $digits;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }
}
