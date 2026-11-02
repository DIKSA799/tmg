<?php

namespace App\Services\Messaging\Email;

use App\Mail\BroadcastEmail;
use App\Services\Messaging\Contracts\ChannelGateway;
use App\Services\Messaging\DispatchResult;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailGateway implements ChannelGateway
{
    /**
     * Send one email through Laravel's configured mailer and return the exchange.
     * There is no HTTP request/response here, so the log records the mailer, the
     * from/to/subject/body and the transport message id instead.
     */
    public function send(string $recipient, ?string $subject, string $body, string $sender): DispatchResult
    {
        $mailer = (string) config('mail.default');
        $from = $sender !== '' ? $sender : (string) config('mail.from.address');
        $subjectLine = $subject !== null && $subject !== '' ? $subject : '(no subject)';

        $requestBody = [
            'mailer' => $mailer,
            'from' => $from,
            'to' => $recipient,
            'subject' => $subjectLine,
            'body' => $body,
        ];

        try {
            $sent = Mail::to($recipient)->send(new BroadcastEmail($subjectLine, $body, $from));
            $messageId = $sent?->getMessageId();

            return new DispatchResult(
                ok: true,
                providerMessageId: $messageId,
                providerStatus: 'sent',
                error: null,
                requestUrl: null,
                requestHeaders: ['transport' => $mailer],
                requestBody: $requestBody,
                responseStatus: null,
                responseHeaders: [],
                responseBody: $messageId,
            );
        } catch (Throwable $exception) {
            return new DispatchResult(
                ok: false,
                providerMessageId: null,
                providerStatus: null,
                error: $exception->getMessage(),
                requestUrl: null,
                requestHeaders: ['transport' => $mailer],
                requestBody: $requestBody,
                responseStatus: null,
                responseHeaders: [],
                responseBody: null,
            );
        }
    }
}
