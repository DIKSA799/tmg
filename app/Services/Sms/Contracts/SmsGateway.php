<?php

namespace App\Services\Sms\Contracts;

use App\Services\Sms\SmsResult;

interface SmsGateway
{
    /**
     * Send one message to a single recipient and return the full exchange so it
     * can be recorded for troubleshooting.
     */
    public function send(string $phone, string $message, string $senderId): SmsResult;
}
