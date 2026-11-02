<?php

namespace App\Services\Messaging\Sms;

use App\Services\Messaging\Concerns\ReadsHttpResponses;
use App\Services\Messaging\Contracts\ChannelGateway;
use App\Services\Messaging\DispatchResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class TermiiGateway implements ChannelGateway
{
    use ReadsHttpResponses;

    /**
     * Send one message through Termii's single-recipient endpoint and return the
     * full exchange. One request per number keeps the recorded request/response
     * unambiguous for the console.
     */
    public function send(string $recipient, ?string $subject, string $body, string $sender): DispatchResult
    {
        $payload = [
            'api_key' => (string) config('services.termii.key'),
            'to' => ltrim($recipient, '+'),
            'from' => $sender,
            'sms' => $body,
            'type' => (string) config('services.termii.type', 'plain'),
            'channel' => (string) config('services.termii.channel', 'generic'),
        ];

        $url = rtrim((string) config('services.termii.base_url'), '/').'/api/sms/send';
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        // Never persist the live key; the recorded body is redacted.
        $recorded = [...$payload, 'api_key' => '••••redacted'];

        try {
            $response = Http::withHeaders($headers)
                ->connectTimeout(5)
                ->timeout((int) config('services.termii.timeout', 10))
                ->post($url, $payload);
        } catch (Throwable $exception) {
            return new DispatchResult(
                ok: false,
                providerMessageId: null,
                providerStatus: null,
                error: $exception->getMessage(),
                requestUrl: $url,
                requestHeaders: $headers,
                requestBody: $recorded,
                responseStatus: null,
                responseHeaders: [],
                responseBody: null,
            );
        }

        return $this->interpret($url, $headers, $recorded, $response);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $recorded
     */
    private function interpret(string $url, array $headers, array $recorded, Response $response): DispatchResult
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        $ok = $response->successful();

        if (array_key_exists('code', $body)) {
            $ok = $ok && $body['code'] === 'ok';
        }

        $messageId = $body['message_id'] ?? $body['message_id_str'] ?? null;

        return new DispatchResult(
            ok: $ok,
            providerMessageId: $this->stringOrNull($messageId),
            providerStatus: $this->stringOrNull($body['message'] ?? null),
            error: $ok ? null : $this->errorFrom($response),
            requestUrl: $url,
            requestHeaders: $headers,
            requestBody: $recorded,
            responseStatus: $response->status(),
            responseHeaders: $response->headers(),
            responseBody: $response->body(),
        );
    }
}
