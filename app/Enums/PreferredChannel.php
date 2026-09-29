<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PreferredChannel: string
{
    use HasLabel;

    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case PhoneCall = 'phone_call';
    case MobileApp = 'mobile_app';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
            self::PhoneCall => 'Phone call',
            self::MobileApp => 'Mobile app',
            self::Other => 'Other',
        };
    }
}
