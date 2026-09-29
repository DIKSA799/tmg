<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AgeBand: string
{
    use HasLabel;

    case EighteenToTwentyFour = '18-24';
    case TwentyFiveToThirtyFour = '25-34';
    case ThirtyFiveToFortyFour = '35-44';
    case FortyFiveToFiftyFour = '45-54';
    case FiftyFiveToSixtyFour = '55-64';
    case SixtyFivePlus = '65+';

    public function label(): string
    {
        return match ($this) {
            self::EighteenToTwentyFour => '18–24',
            self::TwentyFiveToThirtyFour => '25–34',
            self::ThirtyFiveToFortyFour => '35–44',
            self::FortyFiveToFiftyFour => '45–54',
            self::FiftyFiveToSixtyFour => '55–64',
            self::SixtyFivePlus => '65+',
        };
    }
}
