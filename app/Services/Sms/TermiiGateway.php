<?php

namespace App\Services\Sms;

use App\Services\Sms\Concerns\ReadsHttpResponses;
use App\Services\Sms\Contracts\SmsGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class TermiiGateway implements SmsGateway
{
    use ReadsHttpResponses;

    /**
     * Send one message through Termii's single-recipient endpoint and return the
     * full exchange. One request per number keeps the recorded request/response
     * unambiguous for the console.
     */
    public function send(string $phone, string $message, string $senderId): SmsResult
    {
        $payload = [
            'api_key' => (string) config('services.termii.key'),
            'to' => ltrim($phone, '+'),
            'from' => $senderId,
            'sms' => $message,
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
            return new SmsResult(
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
    private function interpret(string $url, array $headers, array $recorded, Response $response): SmsResult
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        $ok = $response->successful();

        if (array_key_exists('code', $body)) {
            $ok = $ok && $body['code'] === 'ok';
        }

        $messageId = $body['message_id'] ?? $body['message_id_str'] ?? null;

        return new SmsResult(
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
