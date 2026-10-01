<?php

namespace App\Actions;

use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use App\Support\Abbreviator;

/**
 * Builds a human-trackable record reference from the captured geography:
 *
 *     TMG-{state}-{lga}-{ward}-{polling unit}-{random suffix}
 *     e.g. TMG-DEL-NDW-UTO-033-K7Q2M9
 *          TMG-RIV-DEG-BAK1-002-M9K3X7
 *
 * State/LGA/ward are readable abbreviations (see {@see Abbreviator}); the
 * polling unit is the one short number that pins the record down. The
 * Crockford Base32 suffix (6 chars = 32^6 ≈ 1.07 billion values per polling
 * unit) provides uniqueness that will not be exhausted.
 */
class GenerateVoterReference
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const SUFFIX_LENGTH = 6;

    private const MAX_ATTEMPTS = 8;

    public function __construct(private readonly Abbreviator $abbreviator) {}

    /**
     * Resolve the geography by id and return a reference guaranteed not to
     * collide with an existing record.
     */
    public function handle(int $stateId, int $lgaId, int $wardId, int $pollingUnitId): string
    {
        return $this->make(
            State::query()->find($stateId),
            Lga::query()->find($lgaId),
            Ward::query()->find($wardId),
            PollingUnit::query()->find($pollingUnitId),
        );
    }

    /**
     * Build a reference for known, already-loaded geography, retrying on the
     * (very unlikely) chance the suffix is already taken.
     */
    public function make(?State $state, ?Lga $lga, ?Ward $ward, ?PollingUnit $unit): string
    {
        $prefix = $this->prefix($state, $lga, $ward, $unit);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $reference = $prefix.'-'.$this->suffix();

            if (! VoterRecord::query()->where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        return $prefix.'-'.$this->suffix(self::SUFFIX_LENGTH * 2);
    }

    /**
     * Build a reference without touching the database. Use only where the
     * geography segments are known to be distinct (e.g. bulk seed data).
     */
    public function format(?State $state, ?Lga $lga, ?Ward $ward, ?PollingUnit $unit): string
    {
        return $this->prefix($state, $lga, $ward, $unit).'-'.$this->suffix();
    }

    private function prefix(?State $state, ?Lga $lga, ?Ward $ward, ?PollingUnit $unit): string
    {
        return implode('-', [
            'TMG',
            $this->abbreviator->state($state),
            $this->abbreviator->make($lga?->name),
            $this->abbreviator->make($ward?->name),
            $this->segment($this->pollingUnitCode($unit), 3),
        ]);
    }

    private function pollingUnitCode(?PollingUnit $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        if ($unit->pu_code !== null && $unit->pu_code !== '') {
            return (string) $unit->pu_code;
        }

        $parts = explode('/', (string) $unit->code);

        return (string) end($parts);
    }

    private function segment(?string $code, int $width): string
    {
        $value = strtoupper(preg_replace('/[^a-z0-9]/i', '', (string) $code) ?? '');

        return $value === '' ? str_repeat('0', $width) : str_pad($value, $width, '0', STR_PAD_LEFT);
    }

    private function suffix(int $length = self::SUFFIX_LENGTH): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $suffix = '';

        for ($index = 0; $index < $length; $index++) {
            $suffix .= self::ALPHABET[random_int(0, $max)];
        }

        return $suffix;
    }
}
