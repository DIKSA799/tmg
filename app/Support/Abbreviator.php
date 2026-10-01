<?php

namespace App\Support;

use App\Models\State;

/**
 * Turns a name into a short, fixed-width abbreviation for record references.
 *
 * The rule, chosen so that qualifiers survive (e.g. "Albasu" vs "Albasu
 * Central"):
 *
 *   - one word          → its first three letters      Albasu    → ALB
 *   - two or more words → the initial of each word,    Ndokwa West → NDW
 *                         padded from the first word  Ajeromi/Ifelodun → AJI
 *   - a trailing number is kept,                    Bakana I → BAK1 (1)
 *     roman or arabic                                Ward 03 → WAR3
 */
class Abbreviator
{
    /**
     * Standard state abbreviations, keyed by slug.
     *
     * @var array<string, string>
     */
    private const STATE_CODES = [
        'abia' => 'ABI',
        'adamawa' => 'ADM',
        'akwa-ibom' => 'AKW',
        'anambra' => 'ANA',
        'bauchi' => 'BAU',
        'bayelsa' => 'BAY',
        'benue' => 'BEN',
        'borno' => 'BOR',
        'cross-river' => 'CRS',
        'delta' => 'DEL',
        'ebonyi' => 'EBO',
        'edo' => 'EDO',
        'ekiti' => 'EKI',
        'enugu' => 'ENU',
        'fct' => 'FCT',
        'gombe' => 'GOM',
        'imo' => 'IMO',
        'jigawa' => 'JIG',
        'kaduna' => 'KAD',
        'kano' => 'KAN',
        'katsina' => 'KAT',
        'kebbi' => 'KEB',
        'kogi' => 'KOG',
        'kwara' => 'KWA',
        'lagos' => 'LAG',
        'nasarawa' => 'NAS',
        'niger' => 'NIG',
        'ogun' => 'OGU',
        'ondo' => 'OND',
        'osun' => 'OSU',
        'oyo' => 'OYO',
        'plateau' => 'PLA',
        'rivers' => 'RIV',
        'sokoto' => 'SOK',
        'taraba' => 'TAR',
        'yobe' => 'YOB',
        'zamfara' => 'ZAM',
    ];

    public function state(?State $state): string
    {
        if ($state === null) {
            return 'UNK';
        }

        return self::STATE_CODES[$state->slug] ?? $this->make($state->name);
    }

    public function make(?string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 ]/', ' ', (string) $name) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        if ($clean === '') {
            return 'UNK';
        }

        $tokens = array_values(array_filter(
            explode(' ', strtoupper($clean)),
            static fn (string $token): bool => $token !== '',
        ));

        [$tokens, $number] = $this->splitNumber($tokens);

        if ($tokens === []) {
            return 'UNK';
        }

        $base = count($tokens) === 1
            ? substr($tokens[0], 0, 3)
            : $this->initials($tokens);

        return str_pad(substr($base, 0, 3), 3, 'X').$number;
    }

    /**
     * Peel a trailing number (roman or arabic) off the token list.
     *
     * @param  list<string>  $tokens
     * @return array{0: list<string>, 1: string}
     */
    private function splitNumber(array $tokens): array
    {
        $last = end($tokens);

        if ($last === false) {
            return [$tokens, ''];
        }

        if (preg_match('/^[IVX]+$/', $last) === 1 && ($value = $this->romanToInt($last)) !== null) {
            array_pop($tokens);

            return [array_values($tokens), (string) $value];
        }

        if (preg_match('/^(.*?)(\d+)$/', $last, $matches) === 1) {
            array_pop($tokens);

            if ($matches[1] !== '') {
                $tokens[] = $matches[1];
            }

            return [array_values($tokens), (string) (int) $matches[2]];
        }

        return [$tokens, ''];
    }

    /**
     * @param  list<string>  $tokens
     */
    private function initials(array $tokens): string
    {
        $initials = '';

        foreach (array_slice($tokens, 0, 3) as $token) {
            $initials .= $token[0];
        }

        $first = $tokens[0];
        $index = 1;

        while (strlen($initials) < 3 && $index < strlen($first)) {
            $initials .= $first[$index];
            $index++;
        }

        return $initials;
    }

    private function romanToInt(string $roman): ?int
    {
        $map = ['I' => 1, 'V' => 5, 'X' => 10];
        $total = 0;
        $previous = 0;

        for ($index = strlen($roman) - 1; $index >= 0; $index--) {
            $value = $map[$roman[$index]] ?? null;

            if ($value === null) {
                return null;
            }

            $total += $value < $previous ? -$value : $value;
            $previous = max($previous, $value);
        }

        return $total > 0 ? $total : null;
    }
}
