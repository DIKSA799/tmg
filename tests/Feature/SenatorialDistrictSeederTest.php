<?php

namespace Tests\Feature;

use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Database\Seeders\SenatorialDistrictSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SenatorialDistrictSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_seeder_maps_all_774_lgas_into_109_districts(): void
    {
        /** @var array<string, array<string, array<int, string>>> $data */
        $data = require config('ambassadors.districts.data');

        $this->assertCount(37, $data);

        $now = now();
        $stateRows = [];
        $stateCode = 1;

        foreach ($data as $stateName => $districts) {
            $stateRows[] = [
                'name' => $stateName,
                'slug' => Str::slug($stateName),
                'code' => (string) $stateCode++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('states')->insert($stateRows);

        $stateIds = DB::table('states')->pluck('id', 'name');
        $lgaRows = [];
        $lgaCode = 1;

        foreach ($data as $stateName => $districts) {
            foreach ($districts as $lgaNames) {
                foreach ($lgaNames as $lgaName) {
                    $lgaRows[] = [
                        'state_id' => $stateIds[$stateName],
                        'name' => $lgaName,
                        'slug' => Str::slug($lgaName),
                        'code' => (string) $lgaCode++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($lgaRows, 200) as $chunk) {
            DB::table('lgas')->insert($chunk);
        }

        $this->assertSame(774, Lga::query()->count());

        $this->seed(SenatorialDistrictSeeder::class);

        $this->assertSame(109, SenatorialDistrict::query()->count());
        $this->assertSame(0, Lga::query()->whereNull('senatorial_district_id')->count());

        $deltaNorth = Lga::query()
            ->where('name', 'Ndokwa West')
            ->with('senatorialDistrict')
            ->sole();

        $this->assertSame('Delta North', $deltaNorth->senatorialDistrict?->name);

        $fct = State::query()->where('name', 'FCT')->sole();

        $this->assertSame(1, $fct->senatorialDistricts()->count());
        $this->assertSame('Federal Capital Territory', $fct->senatorialDistricts()->sole()->name);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_districts(): void
    {
        $state = State::factory()->create(['name' => 'Abia', 'slug' => 'abia', 'code' => '1']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ikwuano', 'slug' => 'ikwuano', 'code' => '1']);

        $this->seed(SenatorialDistrictSeeder::class);
        $this->seed(SenatorialDistrictSeeder::class);

        $this->assertSame(3, $state->senatorialDistricts()->count());
        $this->assertSame(
            'Abia Central',
            $lga->fresh()->senatorialDistrict?->name,
        );
    }

    public function test_the_seeder_repairs_ambassadors_created_without_a_district(): void
    {
        $state = State::factory()->create(['name' => 'Abia', 'slug' => 'abia', 'code' => '1']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ikwuano', 'slug' => 'ikwuano', 'code' => '1']);
        $ward = Ward::factory()->for($lga)->create();
        $unit = PollingUnit::factory()->for($ward)->create();

        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-ABI-IKW-ABC-001-ABC123',
            'phone' => '+2348031234567',
            'email' => 'abia@example.com',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'polling_unit_id' => $unit->id,
        ]);

        $ambassador = Ambassador::factory()->create([
            'voter_record_id' => $record->id,
            'name' => $record->full_name,
            'phone' => $record->phone,
            'email' => $record->email,
            'password' => $record->reference,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'polling_unit_id' => $unit->id,
            'senatorial_district_id' => null,
        ]);

        $this->assertNull($ambassador->senatorial_district_id);

        $this->seed(SenatorialDistrictSeeder::class);

        $central = SenatorialDistrict::query()
            ->where('state_id', $state->id)
            ->where('slug', 'abia-central')
            ->sole();

        $this->assertSame($central->id, $ambassador->fresh()->senatorial_district_id);
    }
}
