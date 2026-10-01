<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PvcStatus: string
{
    use HasLabel;

    case Collected = 'collected';
    case NotCollected = 'not_collected';
    case AwaitingCollection = 'awaiting_collection';
    case LostOrDamaged = 'lost_or_damaged';
    // case NotSure = 'not_sure';

    public function label(): string
    {
        return match ($this) {
            self::Collected => 'Collected',
            self::NotCollected => 'Not collected',
            self::AwaitingCollection => 'Awaiting collection',
            self::LostOrDamaged => 'Lost or damaged',
            // self::NotSure => 'Not sure',
        };
    }
}
