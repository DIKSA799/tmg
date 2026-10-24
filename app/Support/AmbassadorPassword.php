<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Ambassador passwords are intentionally case-insensitive.
 *
 * Every password is normalised to uppercase before hashing and before
 * verification, and the UI mirrors that by showing uppercase characters.
 * That way "tmg-del-..." and "TMG-DEL-..." are the same credential.
 */
class AmbassadorPassword
{
    public static function normalize(?string $value): string
    {
        return Str::upper(trim((string) $value));
    }
}
