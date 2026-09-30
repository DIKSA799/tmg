<?php

namespace Tests\Feature;

use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\User;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoterRecordSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ?User $operator = null;

    private function operator(): self
    {
        return $this->actingAs($this->operator ??= User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $state = State::factory()->create();
        $lga = Lga::factory()->for($state)->create();
        $ward = Ward::factory()->for($lga)->create();
        $pollingUnit = PollingUnit::factory()->for($ward)->create();

        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'full_name' => 'Amina Musa',
            'gender' => 'female',
            'age_band' => '25-34',
            'phone' => '0800 000 0000',
            'whatsapp' => '0803 000 0000',
            'email' => 'amina@example.com',
            'volunteer_category' => 'grassroots_mobilisation',
            'occupation' => 'student',
            'has_disability' => false,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'polling_unit_id' => $pollingUnit->id,
            'registered_voter_status' => 'yes',
            'pvc_status' => 'collected',
            'preferred_language' => 'hausa',
            'preferred_channel' => 'whatsapp',
            'consent_to_contact' => true,
            'consent_to_data' => true,
            'device' => ['device_id' => 'device-abc'],
        ], $overrides);
    }

    public function test_submissions_require_an_operator(): void
    {
        $this->postJson('/submissions', [])->assertUnauthorized();
    }

    public function test_valid_payload_creates_a_record_and_returns_201(): void
    {
        $response = $this->operator()->postJson('/submissions', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('duplicate', false)
            ->assertJsonStructure(['ok', 'duplicate', 'reference', 'captured_at', 'message']);

        $this->assertDatabaseCount('voter_records', 1);
        $this->assertDatabaseHas('voter_records', [
            'full_name' => 'Amina Musa',
            'gender' => 'female',
            'phone' => '+2348000000000',
        ]);
    }

    public function test_phone_is_normalised_to_international_format(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['phone' => '0803 123 4567']))->assertCreated();

        $this->assertDatabaseHas('voter_records', ['phone' => '+2348031234567']);
    }

    public function test_whatsapp_is_normalised_to_international_format(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['whatsapp' => '0805 123 4567']))->assertCreated();

        $this->assertDatabaseHas('voter_records', ['whatsapp' => '+2348051234567']);
    }

    public function test_volunteer_details_are_persisted(): void
    {
        $this->operator()->postJson('/submissions', $this->payload([
            'volunteer_category' => 'digital_social_media',
            'occupation' => 'ict_technology',
            'has_disability' => true,
        ]))->assertCreated();

        $this->assertDatabaseHas('voter_records', [
            'volunteer_category' => 'digital_social_media',
            'occupation' => 'ict_technology',
            'has_disability' => true,
        ]);
    }

    public function test_invalid_volunteer_category_returns_422(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['volunteer_category' => 'not-a-category']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('volunteer_category');
    }

    public function test_ip_address_and_device_context_are_captured(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['device' => ['device_id' => 'device-xyz', 'platform' => 'iOS']]))
            ->assertCreated();

        $record = VoterRecord::query()->sole();

        $this->assertSame('device-xyz', $record->device['device_id']);
        $this->assertNotNull($record->ip_address);
        $this->assertNotNull($record->captured_at);
    }

    public function test_retrying_with_the_same_idempotency_key_creates_one_record(): void
    {
        $payload = $this->payload();

        $first = $this->operator()->postJson('/submissions', $payload);
        $second = $this->operator()->postJson('/submissions', $payload);

        $first->assertCreated()->assertJsonPath('duplicate', false);
        $second->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame($first->json('reference'), $second->json('reference'));
        $this->assertDatabaseCount('voter_records', 1);
    }

    public function test_invalid_phone_number_returns_422(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['phone' => '12345']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_lga_that_does_not_belong_to_the_state_returns_422(): void
    {
        $payload = $this->payload();
        $foreignLga = Lga::factory()->create();

        $this->operator()->postJson('/submissions', array_merge($payload, ['lga_id' => $foreignLga->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('lga_id');
    }

    public function test_polling_unit_that_does_not_belong_to_the_ward_returns_422(): void
    {
        $payload = $this->payload();
        $foreignUnit = PollingUnit::factory()->create();

        $this->operator()->postJson('/submissions', array_merge($payload, ['polling_unit_id' => $foreignUnit->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('polling_unit_id');
    }

    public function test_missing_data_processing_consent_returns_422(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['consent_to_data' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('consent_to_data');
    }

    public function test_selecting_other_language_requires_details(): void
    {
        $this->operator()->postJson('/submissions', $this->payload(['preferred_language' => 'other']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('preferred_language_other');

        $this->operator()->postJson('/submissions', $this->payload([
            'preferred_language' => 'other',
            'preferred_language_other' => 'Tiv',
        ]))->assertCreated();
    }
}
