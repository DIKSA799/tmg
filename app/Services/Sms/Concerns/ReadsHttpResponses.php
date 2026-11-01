<?php

namespace App\Services\Sms\Concerns;

use Illuminate\Http\Client\Response;

trait ReadsHttpResponses
{
    /**
     * The most useful error string from a non-successful response: its body when
     * present, otherwise a short status note.
     */
    private function errorFrom(Response $response): string
    {
        $body = $response->body();

        return trim($body) !== ''
            ? $body
            : 'Request failed with HTTP '.$response->status().' and an empty body.';
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
