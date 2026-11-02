<?php

namespace App\Services\Messaging;

/**
 * The complete outcome of a single send: whether it succeeded and the exact
 * request and response so the console can show them for troubleshooting.
 */
final readonly class DispatchResult
{
    /**
     * @param  array<string, mixed>  $requestHeaders
     * @param  array<string, mixed>  $requestBody
     * @param  array<string, mixed>  $responseHeaders
     */
    public function __construct(
        public bool $ok,
        public ?string $providerMessageId,
        public ?string $providerStatus,
        public ?string $error,
        public ?string $requestUrl,
        public array $requestHeaders,
        public array $requestBody,
        public ?int $responseStatus,
        public array $responseHeaders,
        public ?string $responseBody,
    ) {}
}
