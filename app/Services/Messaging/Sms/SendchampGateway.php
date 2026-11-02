<?php

namespace App\Services\Messaging\Sms;

use App\Services\Messaging\Concerns\ReadsHttpResponses;
use App\Services\Messaging\Contracts\ChannelGateway;
use App\Services\Messaging\DispatchResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendchampGateway implements ChannelGateway
{
    use ReadsHttpResponses;

    /**
     * Send one message through Sendchamp's SMS endpoint and return the full
     * exchange. Sendchamp takes an array of recipients; we send one per request
     * so each row records its own request and response.
     */
    public function send(string $recipient, ?string $subject, string $body, string $sender): DispatchResult
    {
        $payload = [
            'to' => [ltrim($recipient, '+')],
            'message' => $body,
            'sender_name' => $sender,
            'route' => (string) config('services.sendchamp.route', 'non_dnd'),
        ];

        $url = rtrim((string) config('services.sendchamp.base_url'), '/').'/sms/send';
        $headers = [
            'Authorization' => 'Bearer '.(string) config('services.sendchamp.key'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Never persist the live token; the recorded header is redacted.
        $recorded = [...$headers, 'Authorization' => 'Bearer ••••redacted'];

        try {
            $response = Http::withHeaders($headers)
                ->connectTimeout(5)
                ->timeout((int) config('services.sendchamp.timeout', 10))
                ->post($url, $payload);
        } catch (Throwable $exception) {
            return new DispatchResult(
                ok: false,
                providerMessageId: null,
                providerStatus: null,
                error: $exception->getMessage(),
                requestUrl: $url,
                requestHeaders: $recorded,
                requestBody: $payload,
                responseStatus: null,
                responseHeaders: [],
                responseBody: null,
            );
        }

        return $this->interpret($url, $recorded, $payload, $response);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    private function interpret(string $url, array $headers, array $payload, Response $response): DispatchResult
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        $ok = $response->successful() && ($body['status'] ?? null) === 'success';

        $messageId = $data['id'] ?? $data['reference'] ?? null;

        return new DispatchResult(
            ok: $ok,
            providerMessageId: $this->stringOrNull($messageId),
            providerStatus: $this->stringOrNull($data['status'] ?? null) ?? $this->stringOrNull($body['message'] ?? null),
            error: $ok ? null : $this->errorFrom($response),
            requestUrl: $url,
            requestHeaders: $headers,
            requestBody: $payload,
            responseStatus: $response->status(),
            responseHeaders: $response->headers(),
            responseBody: $response->body(),
        );
    }
}
