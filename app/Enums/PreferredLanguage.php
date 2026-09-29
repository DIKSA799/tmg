<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PreferredLanguage: string
{
    use HasLabel;

    case English = 'english';
    case Hausa = 'hausa';
    case Yoruba = 'yoruba';
    case Igbo = 'igbo';
    case Fulfulde = 'fulfulde';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Hausa => 'Hausa',
            self::Yoruba => 'Yoruba',
            self::Igbo => 'Igbo',
            self::Fulfulde => 'Fulfulde',
            self::Other => 'Other',
        };
    }
}
