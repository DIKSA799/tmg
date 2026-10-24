<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolve the login identity: an email address or a Nigerian phone number.
 */
class AmbassadorIdentity
{
    /**
     * @return array{0: 'email'|'phone', 1: string}
     */
    public static function parse(?string $identity): array
    {
        $identity = trim((string) $identity);

        if (str_contains($identity, '@')) {
            return ['email', Str::lower($identity)];
        }

        return ['phone', PhoneNumber::normalize($identity) ?? $identity];
    }
}
