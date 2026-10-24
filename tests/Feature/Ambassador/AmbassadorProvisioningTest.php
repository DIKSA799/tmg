<?php

namespace Tests\Feature\Ambassador;

use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AmbassadorProvisioningTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array{state: State, district: SenatorialDistrict, lga: Lga, ward: Ward, unit: PollingUnit}  $zone
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $zone, array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'full_name' => 'Amina Musa',
            'gender' => 'female',
            'age_band' => '25-34',
            'phone' => '08030000001',
            'email' => 'amina@example.com',
            'volunteer_category' => 'grassroots_mobilisation',
            'occupation' => 'student',
            'has_disability' => false,
            'registered_voter_status' => 'yes',
            'pvc_status' => 'collected',
            'preferred_language' => 'hausa',
            'preferred_channel' => 'whatsapp',
            'consent_to_contact' => true,
            'consent_to_data' => true,
            'pledge_accepted' => true,
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ], $overrides);
    }

    public function test_a_new_registration_creates_an_ambassador_login(): void
    {
        $zone = $this->makeZone();

        $response = $this->postJson('/submissions', $this->payload($zone))->assertCreated();

        $reference = $response->json('reference');

        $ambassador = Ambassador::query()->sole();

        $this->assertSame('+2348030000001', $ambassador->phone);
        $this->assertSame('amina@example.com', $ambassador->email);
        $this->assertSame($zone['district']->id, $ambassador->senatorial_district_id);
        $this->assertSame($zone['state']->id, $ambassador->state_id);
        $this->assertTrue(Hash::check(Str::upper($reference), $ambassador->password));
        $this->assertSame($reference, $ambassador->voterRecord->reference);
    }

    public function test_the_new_account_can_sign_in_with_the_tmg_number(): void
    {
        $zone = $this->makeZone();

        $reference = $this->postJson('/submissions', $this->payload($zone))
            ->assertCreated()
            ->json('reference');

        $this->post('/tmgamb', [
            'identity' => '08030000001',
            'password' => strtolower($reference),
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticated('ambassador');
    }

    public function test_backfill_creates_missing_accounts_and_is_idempotent(): void
    {
        $zone = $this->makeZone();

        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-TEST-ABC-001-XYZ123',
            'phone' => '+2348031111111',
            'email' => 'existing@example.com',
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        $this->artisan('tmg:backfill-ambassadors')->assertSuccessful();
        $this->artisan('tmg:backfill-ambassadors')->assertSuccessful();

        $this->assertDatabaseCount('ambassadors', 1);

        $ambassador = Ambassador::query()->sole();

        $this->assertSame($record->id, $ambassador->voter_record_id);
        $this->assertSame('+2348031111111', $ambassador->phone);
        $this->assertSame($zone['district']->id, $ambassador->senatorial_district_id);
        $this->assertTrue(Hash::check('TMG-TEST-ABC-001-XYZ123', $ambassador->password));
    }

    public function test_backfill_does_not_overwrite_an_existing_password(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone, ['password' => 'CUSTOM-PASSWORD']);
        $original = $ambassador->password;

        $this->artisan('tmg:backfill-ambassadors')->assertSuccessful();

        $this->assertSame($original, $ambassador->fresh()->password);
    }

    public function test_an_email_already_used_by_another_account_is_dropped(): void
    {
        $zone = $this->makeZone();
        $this->ambassadorInZone($zone, ['email' => 'shared@example.com']);

        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-TEST-DEF-002-ABC456',
            'phone' => '+2348032222222',
            'email' => 'shared@example.com',
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        $this->artisan('tmg:backfill-ambassadors')->assertSuccessful();

        $created = Ambassador::query()->where('voter_record_id', $record->id)->sole();

        $this->assertNull($created->email);
        $this->assertSame('+2348032222222', $created->phone);
    }
}
