<?php

namespace Tests\Feature;

use App\Models\Lga;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationLookupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_locate_matches_state_lga_and_ward_from_coordinates(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.bigdatacloud.net/*' => Http::response([
                'countryName' => 'Nigeria',
                'countryCode' => 'NG',
                'principalSubdivision' => 'Delta',
                'city' => 'Ndokwa West',
                'locality' => 'Utagba Ogbe',
            ]),
        ]);

        $state = State::factory()->create(['name' => 'Delta', 'slug' => 'delta', 'code' => '10']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ndokwa West', 'slug' => 'ndokwa-west', 'code' => '12']);
        $ward = Ward::factory()->for($lga)->create(['name' => 'Utagba Ogbe', 'slug' => 'utagba-ogbe', 'code' => '01']);

        $this->postJson('/geography/locate', ['latitude' => 5.9, 'longitude' => 6.4])
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.state.id', $state->id)
            ->assertJsonPath('data.lga.id', $lga->id)
            ->assertJsonPath('data.ward.id', $ward->id);
    }

    public function test_locate_reports_unmatched_when_the_geocoder_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.bigdatacloud.net/*' => Http::response(null, 500)]);

        $this->postJson('/geography/locate', ['latitude' => 5.9, 'longitude' => 6.4])
            ->assertOk()
            ->assertJsonPath('data.matched', false)
            ->assertJsonPath('data.reason', 'unavailable');
    }

    public function test_locate_reports_disabled_when_geocoding_is_switched_off(): void
    {
        config(['services.geocoding.enabled' => false]);
        Http::preventStrayRequests();

        $this->postJson('/geography/locate', ['latitude' => 5.9, 'longitude' => 6.4])
            ->assertOk()
            ->assertJsonPath('data.matched', false)
            ->assertJsonPath('data.reason', 'disabled');
    }

    public function test_locate_rejects_coordinates_outside_the_valid_range(): void
    {
        $this->postJson('/geography/locate', ['latitude' => 120, 'longitude' => 200])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }
}
