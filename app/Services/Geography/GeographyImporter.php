<?php

namespace App\Services\Geography;

use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class GeographyImporter
{
    private const STATE_COLUMN = 1;

    private const LGA_COLUMN = 2;

    private const WARD_COLUMN = 3;

    private const STATE_CODE_COLUMN = 4;

    private const LGA_CODE_COLUMN = 5;

    private const WARD_CODE_COLUMN = 6;

    private const PU_CODE_COLUMN = 7;

    private const CODE_COLUMN = 8;

    private const LOCATION_COLUMN = 9;

    private const CHUNK_SIZE = 1000;

    /**
     * Import the polling unit CSV into the geography hierarchy.
     *
     * The import is idempotent: existing states, LGAs and wards are reused and
     * polling units are upserted on their unique `code`.
     *
     * @return array{states: int, lgas: int, wards: int, polling_units: int}
     */
    public function import(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Polling unit source file is not readable at [{$path}].");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open polling unit source file at [{$path}].");
        }

        /** @var array<string, State> $states */
        $states = [];
        /** @var array<string, Lga> $lgas */
        $lgas = [];
        /** @var array<string, Ward> $wards */
        $wards = [];
        /** @var list<array<string, mixed>> $units */
        $units = [];
        $counts = ['states' => 0, 'lgas' => 0, 'wards' => 0, 'polling_units' => 0];
        $unitsBefore = PollingUnit::query()->count();

        try {
            $header = fgetcsv($handle, 0, ',', '"', '');

            if ($header === false) {
                return $counts;
            }

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) < 10) {
                    continue;
                }

                $stateSlug = Str::slug($row[self::STATE_COLUMN]);

                if ($stateSlug === '') {
                    continue;
                }

                $state = $states[$stateSlug] ??= $this->resolveState($stateSlug, $row[self::STATE_COLUMN], $row[self::STATE_CODE_COLUMN], $counts);

                $lgaKey = $stateSlug.'|'.$row[self::LGA_CODE_COLUMN];
                $lga = $lgas[$lgaKey] ??= $this->resolveLga($state, $stateSlug, $row[self::LGA_COLUMN], $row[self::LGA_CODE_COLUMN], $counts);

                $wardKey = $lgaKey.'|'.$row[self::WARD_CODE_COLUMN];
                $ward = $wards[$wardKey] ??= $this->resolveWard($lga, $row[self::WARD_COLUMN], $row[self::WARD_CODE_COLUMN], $counts);

                $code = trim((string) $row[self::CODE_COLUMN]);
                $location = trim((string) $row[self::LOCATION_COLUMN]);

                if ($code === '' || $location === '') {
                    continue;
                }

                $units[] = [
                    'ward_id' => $ward->id,
                    'name' => $location,
                    'code' => $code,
                    'pu_code' => trim((string) $row[self::PU_CODE_COLUMN]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($units) >= self::CHUNK_SIZE) {
                    $this->flushUnits($units);
                    $units = [];
                }
            }
        } finally {
            fclose($handle);
        }

        if ($units !== []) {
            $this->flushUnits($units);
        }

        $counts['polling_units'] = PollingUnit::query()->count() - $unitsBefore;

        Cache::flush();

        return $counts;
    }

    /**
     * @param  array{states: int, lgas: int, wards: int, polling_units: int}  $counts
     */
    private function resolveState(string $slug, string $name, string $code, array &$counts): State
    {
        $state = State::firstOrCreate(
            ['slug' => $slug],
            ['name' => $this->displayName($name), 'code' => trim($code)],
        );

        if ($state->wasRecentlyCreated) {
            $counts['states']++;
        }

        return $state;
    }

    /**
     * @param  array{states: int, lgas: int, wards: int, polling_units: int}  $counts
     */
    private function resolveLga(State $state, string $stateSlug, string $name, string $code, array &$counts): Lga
    {
        $lga = Lga::firstOrCreate(
            ['state_id' => $state->id, 'code' => trim($code)],
            ['name' => $this->displayName($name), 'slug' => Str::slug($name)],
        );

        if ($lga->wasRecentlyCreated) {
            $counts['lgas']++;
        }

        return $lga;
    }

    /**
     * @param  array{states: int, lgas: int, wards: int, polling_units: int}  $counts
     */
    private function resolveWard(Lga $lga, string $name, string $code, array &$counts): Ward
    {
        $ward = Ward::firstOrCreate(
            ['lga_id' => $lga->id, 'code' => trim($code)],
            ['name' => $this->displayName($name), 'slug' => Str::slug($name)],
        );

        if ($ward->wasRecentlyCreated) {
            $counts['wards']++;
        }

        return $ward;
    }

    /**
     * @param  list<array<string, mixed>>  $units
     */
    private function flushUnits(array $units): void
    {
        PollingUnit::upsert(
            $units,
            ['code'],
            ['ward_id', 'name', 'pu_code', 'updated_at'],
        );
    }

    private function displayName(string $name): string
    {
        $name = trim($name);

        return match (Str::slug($name)) {
            'fct' => 'FCT',
            default => Str::title($name),
        };
    }
}
