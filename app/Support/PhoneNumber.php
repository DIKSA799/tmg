<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a Nigerian phone number to E.164 (+234) format.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '234')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+234'.$digits;
    }
}
