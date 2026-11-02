<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CampaignChannel: string
{
    use HasLabel;

    case Sms = 'sms';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Email => 'Email',
        };
    }

    public function recipientLabel(): string
    {
        return match ($this) {
            self::Sms => 'phone number',
            self::Email => 'email address',
        };
    }

    public function recipientFieldLabel(): string
    {
        return match ($this) {
            self::Sms => 'Phone numbers',
            self::Email => 'Email addresses',
        };
    }

    /**
     * Whether this channel needs a subject line.
     */
    public function needsSubject(): bool
    {
        return $this === self::Email;
    }
}
