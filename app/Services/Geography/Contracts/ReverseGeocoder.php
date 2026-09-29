<?php

namespace App\Services\Geography\Contracts;

interface ReverseGeocoder
{
    /**
     * Resolve a coordinate pair into administrative address names.
     *
     * @return array{country: ?string, country_code: ?string, state: ?string, lga: ?string, locality: ?string}|null
     */
    public function reverse(float $latitude, float $longitude): ?array;
}
