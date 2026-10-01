<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise any way a Nigerian mobile number is typed into E.164 (+234).
     *
     * Accepts a leading "+", the "00" international prefix, the "234" country
     * code, a "0" trunk prefix, or a bare national number — with spaces, dashes
     * and brackets ignored. Returns null when what remains is not a valid
     * Nigerian mobile number (10 digits starting 7, 8 or 9).
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '234')) {
            $digits = substr($digits, 3);
        }

        $digits = ltrim($digits, '0');

        if (preg_match('/^[789]\d{9}$/', $digits) !== 1) {
            return null;
        }

        return '+234'.$digits;
    }
}
