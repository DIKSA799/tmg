<?php

namespace Tests;

use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Environment values that must win over the host shell environment.
     *
     * The host process can export values such as APP_ENV=local, and PHPUnit's
     * `<env>` directive only writes $_ENV/$_SERVER when the key is unset. Forcing
     * both here keeps the suite hermetic regardless of the developer's shell.
     *
     * @var array<string, string>
     */
    private const FORCED_ENV = [
        'APP_ENV' => 'testing',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ];

    protected function setUp(): void
    {
        foreach (self::FORCED_ENV as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }

    /**
     * Build one coherent geography: state -> district -> LGA -> ward -> unit.
     *
     * @return array{state: State, district: SenatorialDistrict, lga: Lga, ward: Ward, unit: PollingUnit}
     */
    protected function makeZone(?State $state = null, ?SenatorialDistrict $district = null): array
    {
        $state ??= State::factory()->create();
        $district ??= SenatorialDistrict::factory()->for($state)->create();
        $lga = Lga::factory()->for($state)->create(['senatorial_district_id' => $district->id]);
        $ward = Ward::factory()->for($lga)->create();
        $unit = PollingUnit::factory()->for($ward)->create();

        return compact('state', 'district', 'lga', 'ward', 'unit');
    }

    /**
     * An ambassador with a voter record inside the given zone.
     *
     * @param  array{state: State, district: SenatorialDistrict, lga: Lga, ward: Ward, unit: PollingUnit}  $zone
     * @param  array<string, mixed>  $overrides
     */
    protected function ambassadorInZone(array $zone, array $overrides = []): Ambassador
    {
        $reference = $overrides['reference'] ?? sprintf(
            'TMG-%s-%s-%s-%03d-%s',
            Str::upper(Str::random(3)),
            Str::upper(Str::random(3)),
            Str::upper(Str::random(3)),
            fake()->numberBetween(1, 999),
            Str::upper(Str::random(6)),
        );

        $phone = $overrides['phone'] ?? '+234803'.fake()->unique()->numerify('#######');
        $email = $overrides['email'] ?? fake()->unique()->safeEmail();

        $record = VoterRecord::factory()->create([
            'reference' => $reference,
            'phone' => $phone,
            'email' => $email,
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        return Ambassador::factory()->create(array_merge([
            'voter_record_id' => $record->id,
            'name' => $overrides['name'] ?? $record->full_name,
            'phone' => $phone,
            'email' => $email,
            'password' => $reference,
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
            'senatorial_district_id' => $zone['district']->id,
        ], $overrides));
    }
}
