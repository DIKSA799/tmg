<?php

namespace App\Services\Geography;

use App\Services\Geography\Contracts\ReverseGeocoder;
use Illuminate\Support\Facades\Http;
use Throwable;

class BigDataCloudGeocoder implements ReverseGeocoder
{
    /**
     * @return array{country: ?string, country_code: ?string, state: ?string, lga: ?string, locality: ?string}|null
     */
    public function reverse(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.geocoding.timeout', 5))
                ->get(config('services.geocoding.endpoint'), [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'localityLanguage' => 'en',
                ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return null;
        }

        return [
            'country' => $data['countryName'] ?? null,
            'country_code' => $data['countryCode'] ?? null,
            'state' => $data['principalSubdivision'] ?? null,
            'lga' => $data['city'] ?? $data['locality'] ?? null,
            'locality' => $data['locality'] ?? null,
        ];
    }
}
