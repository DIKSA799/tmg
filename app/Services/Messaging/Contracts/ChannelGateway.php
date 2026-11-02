<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\DispatchResult;

interface ChannelGateway
{
    /**
     * Send one message to a single recipient and return the full exchange so it
     * can be recorded for troubleshooting.
     *
     * @param  string  $recipient  phone number (SMS) or email address
     * @param  ?string  $subject  email subject; ignored by SMS
     * @param  string  $body  the message text
     * @param  string  $sender  sender id (SMS) or from address (email)
     */
    public function send(string $recipient, ?string $subject, string $body, string $sender): DispatchResult;
}
