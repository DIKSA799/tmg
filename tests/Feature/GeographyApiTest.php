<?php

namespace Tests\Feature;

use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GeographyApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_states_endpoint_returns_states_ordered_by_name(): void
    {
        State::factory()->create(['name' => 'Zamfara', 'slug' => 'zamfara', 'code' => '36']);
        State::factory()->create(['name' => 'Abia', 'slug' => 'abia', 'code' => '01']);

        $this->getJson('/geography/states')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Abia')
            ->assertJsonPath('data.1.name', 'Zamfara')
            ->assertJsonStructure(['data' => [['id', 'name', 'code']]]);
    }

    public function test_lgas_endpoint_returns_only_the_lgas_of_the_state(): void
    {
        $state = State::factory()->create(['name' => 'Delta', 'slug' => 'delta', 'code' => '10']);
        $otherState = State::factory()->create(['name' => 'Abia', 'slug' => 'abia', 'code' => '01']);
        Lga::factory()->for($state)->create(['name' => 'Ndokwa West', 'slug' => 'ndokwa-west', 'code' => '12']);
        Lga::factory()->for($otherState)->create(['name' => 'Aba North', 'slug' => 'aba-north', 'code' => '01']);

        $this->getJson("/geography/states/{$state->id}/lgas")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ndokwa West');
    }

    public function test_wards_endpoint_is_scoped_to_the_lga(): void
    {
        $lga = Lga::factory()->create();
        $otherLga = Lga::factory()->create();
        Ward::factory()->for($lga)->create(['name' => 'Utagba Ogbe', 'slug' => 'utagba-ogbe', 'code' => '01']);
        Ward::factory()->for($otherLga)->create(['name' => 'Umuebu', 'slug' => 'umuebu', 'code' => '02']);

        $this->getJson("/geography/lgas/{$lga->id}/wards")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Utagba Ogbe');
    }

    public function test_polling_units_endpoint_is_scoped_to_the_ward_and_ordered_by_name(): void
    {
        $ward = Ward::factory()->create();
        $otherWard = Ward::factory()->create();
        PollingUnit::factory()->for($ward)->create(['name' => 'Zeta Square', 'code' => '01/01/01/002', 'pu_code' => '002']);
        PollingUnit::factory()->for($ward)->create(['name' => 'Alpha Square', 'code' => '01/01/01/001', 'pu_code' => '001']);
        PollingUnit::factory()->for($otherWard)->create(['name' => 'Other Unit', 'code' => '01/01/02/001', 'pu_code' => '001']);

        $this->getJson("/geography/wards/{$ward->id}/polling-units")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Square')
            ->assertJsonPath('data.1.name', 'Zeta Square')
            ->assertJsonStructure(['data' => [['id', 'name', 'code', 'pu_code']]]);
    }
}
