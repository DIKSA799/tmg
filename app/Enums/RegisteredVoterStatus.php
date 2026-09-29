<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum RegisteredVoterStatus: string
{
    use HasLabel;

    case Yes = 'yes';
    case No = 'no';
    case NotSure = 'not_sure';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Yes',
            self::No => 'No',
            self::NotSure => 'Not sure',
        };
    }
}
