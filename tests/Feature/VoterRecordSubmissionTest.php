<?php

namespace Tests\Feature;

use App\Jobs\SendAmbassadorWelcomeEmail;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\User;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
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
        $geography = [];

        if (! isset($overrides['state_id'], $overrides['lga_id'], $overrides['ward_id'], $overrides['polling_unit_id'])) {
            $state = State::factory()->create();
            $lga = Lga::factory()->for($state)->create();
            $ward = Ward::factory()->for($lga)->create();
            $pollingUnit = PollingUnit::factory()->for($ward)->create();

            $geography = [
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'ward_id' => $ward->id,
                'polling_unit_id' => $pollingUnit->id,
            ];
        }

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
            'registered_voter_status' => 'yes',
            'pvc_status' => 'collected',
            'preferred_language' => 'hausa',
            'preferred_channel' => 'whatsapp',
            'consent_to_contact' => true,
            'consent_to_data' => true,
            'device' => ['device_id' => 'device-abc'],
        ], $geography, $overrides);
    }

    public function test_guests_can_submit_a_record(): void
    {
        $this->postJson('/submissions', $this->payload())->assertCreated();
    }

    public function test_a_welcome_email_is_queued_for_a_new_registration(): void
    {
        Queue::fake();

        $this->operator()->postJson('/submissions', $this->payload())->assertCreated();

        Queue::assertPushed(SendAmbassadorWelcomeEmail::class, 1);
    }

    public function test_a_registration_still_succeeds_when_the_queue_is_unavailable(): void
    {
        $this->mock(Dispatcher::class, function ($mock): void {
            $mock->shouldReceive('dispatch')->andThrow(new RuntimeException('queue down'));
        });

        $this->operator()->postJson('/submissions', $this->payload())->assertCreated();

        $this->assertDatabaseCount('voter_records', 1);
    }

    public function test_a_duplicate_submission_does_not_send_a_second_email(): void
    {
        Queue::fake();

        $payload = $this->payload();

        $this->operator()->postJson('/submissions', $payload)->assertCreated();
        $this->operator()->postJson('/submissions', $payload)->assertOk();

        Queue::assertPushed(SendAmbassadorWelcomeEmail::class, 1);
    }

    public function test_no_welcome_email_is_queued_without_an_email_address(): void
    {
        Queue::fake();

        $this->operator()->postJson('/submissions', $this->payload(['email' => null]))->assertCreated();

        Queue::assertNotPushed(SendAmbassadorWelcomeEmail::class);
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

    public function test_reference_is_derived_from_the_captured_geography(): void
    {
        $state = State::factory()->create(['name' => 'Delta', 'slug' => 'delta', 'code' => '10']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ndokwa West', 'code' => '12']);
        $ward = Ward::factory()->for($lga)->create(['name' => 'Utagba Ogbe', 'code' => '01']);
        $unit = PollingUnit::factory()->for($ward)->create(['pu_code' => '033', 'code' => '10/12/01/033']);

        $response = $this->operator()->postJson('/submissions', $this->payload([
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'polling_unit_id' => $unit->id,
        ]))->assertCreated();

        $this->assertMatchesRegularExpression('/^TMG-DEL-NDW-UTO-033-[0-9A-Z]{6}$/', $response->json('reference'));
        $this->assertSame($response->json('reference'), VoterRecord::query()->sole()->reference);
    }

    public function test_reference_keeps_a_ward_number_so_named_twins_stay_distinct(): void
    {
        $state = State::factory()->create(['name' => 'Rivers', 'slug' => 'rivers', 'code' => '32']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Degema', 'code' => '08']);

        $references = [];

        foreach (['Bakana I', 'Bakana II'] as $index => $wardName) {
            $ward = Ward::factory()->for($lga)->create(['name' => $wardName, 'code' => '0'.($index + 1)]);
            $unit = PollingUnit::factory()->for($ward)->create(['pu_code' => '002']);

            $references[] = $this->operator()->postJson('/submissions', $this->payload([
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'ward_id' => $ward->id,
                'polling_unit_id' => $unit->id,
            ]))->assertCreated()->json('reference');
        }

        $this->assertStringStartsWith('TMG-RIV-DEG-BAK1-002-', $references[0]);
        $this->assertStringStartsWith('TMG-RIV-DEG-BAK2-002-', $references[1]);
    }

    public function test_references_are_unique_across_records(): void
    {
        $first = $this->operator()->postJson('/submissions', $this->payload())->assertCreated();
        $second = $this->operator()->postJson('/submissions', $this->payload())->assertCreated();

        $this->assertNotSame($first->json('reference'), $second->json('reference'));
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
