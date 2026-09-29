<?php

namespace App\Services\Geography;

use App\Models\Lga;
use App\Models\State;
use App\Models\Ward;
use App\Services\Geography\Contracts\ReverseGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LocationResolver
{
    public function __construct(private ReverseGeocoder $geocoder) {}

    /**
     * Best-effort reverse geocoding into the seeded geography hierarchy.
     *
     * @return array{
     *     matched: bool,
     *     reason: ?string,
     *     state: ?array{id: int, name: string},
     *     lga: ?array{id: int, name: string},
     *     ward: ?array{id: int, name: string},
     *     address: array<string, mixed>
     * }
     */
    public function resolve(float $latitude, float $longitude): array
    {
        if (! config('services.geocoding.enabled')) {
            return $this->unmatched('disabled');
        }

        $key = 'geo:locate:'.round($latitude, 3).':'.round($longitude, 3);

        return Cache::remember(
            $key,
            (int) config('services.geocoding.ttl', 86400),
            fn (): array => $this->match($latitude, $longitude),
        );
    }

    /**
     * @return array{
     *     matched: bool,
     *     reason: ?string,
     *     state: ?array{id: int, name: string},
     *     lga: ?array{id: int, name: string},
     *     ward: ?array{id: int, name: string},
     *     address: array<string, mixed>
     * }
     */
    private function match(float $latitude, float $longitude): array
    {
        $address = $this->geocoder->reverse($latitude, $longitude);

        if ($address === null) {
            return $this->unmatched('unavailable');
        }

        $state = $this->matchState($address['state']);

        if ($state === null) {
            return $this->unmatched('state_not_found', $address);
        }

        $lga = $this->matchLga($state, $address['lga']);
        $ward = $lga === null ? null : $this->matchWard($lga, $address['locality'] ?? $address['lga']);

        return [
            'matched' => true,
            'reason' => null,
            'state' => ['id' => $state->id, 'name' => $state->name],
            'lga' => $lga === null ? null : ['id' => $lga->id, 'name' => $lga->name],
            'ward' => $ward === null ? null : ['id' => $ward->id, 'name' => $ward->name],
            'address' => $address,
        ];
    }

    private function matchState(?string $name): ?State
    {
        $slug = Str::slug((string) $name);

        if ($slug === '') {
            return null;
        }

        return State::query()->where('slug', $slug)->first()
            ?? State::query()->where('slug', 'like', $slug.'%')->orderBy('name')->first();
    }

    private function matchLga(State $state, ?string $name): ?Lga
    {
        $slug = Str::slug((string) $name);

        if ($slug === '') {
            return null;
        }

        return $state->lgas()->where('slug', $slug)->first()
            ?? $state->lgas()->where('slug', 'like', $slug.'%')->orderBy('name')->first()
            ?? $state->lgas()->where('slug', 'like', '%'.$slug.'%')->orderBy('name')->first();
    }

    private function matchWard(Lga $lga, ?string $name): ?Ward
    {
        $slug = Str::slug((string) $name);

        if ($slug === '') {
            return null;
        }

        return $lga->wards()->where('slug', $slug)->first()
            ?? $lga->wards()->where('slug', 'like', $slug.'%')->orderBy('name')->first()
            ?? $lga->wards()->where('slug', 'like', '%'.$slug.'%')->orderBy('name')->first();
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{matched: bool, reason: string, state: null, lga: null, ward: null, address: array<string, mixed>}
     */
    private function unmatched(string $reason, array $address = []): array
    {
        return [
            'matched' => false,
            'reason' => $reason,
            'state' => null,
            'lga' => null,
            'ward' => null,
            'address' => $address,
        ];
    }
}
