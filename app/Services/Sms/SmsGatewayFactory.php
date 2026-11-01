<?php

namespace App\Services\Sms;

use App\Enums\SmsProvider;
use App\Services\Sms\Contracts\SmsGateway;

class SmsGatewayFactory
{
    public function make(SmsProvider $provider): SmsGateway
    {
        return match ($provider) {
            SmsProvider::Termii => app(TermiiGateway::class),
            SmsProvider::Sendchamp => app(SendchampGateway::class),
        };
    }
}
