<?php

namespace Database\Factories;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\Occupation;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Enums\VolunteerCategory;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VoterRecord>
 */
class VoterRecordFactory extends Factory
{
    public function definition(): array
    {
        $state = State::factory()->create();
        $lga = Lga::factory()->for($state)->create();
        $ward = Ward::factory()->for($lga)->create();
        $pollingUnit = PollingUnit::factory()->for($ward)->create();

        return [
            'public_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'age_band' => fake()->randomElement(AgeBand::cases())->value,
            'phone' => '+234'.fake()->numerify('803#######'),
            'whatsapp' => '+234'.fake()->numerify('805#######'),
            'email' => fake()->safeEmail(),
            'volunteer_category' => fake()->randomElement(VolunteerCategory::cases())->value,
            'occupation' => fake()->randomElement(Occupation::cases())->value,
            'has_disability' => fake()->boolean(10),
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'polling_unit_id' => $pollingUnit->id,
            'registered_voter_status' => fake()->randomElement(RegisteredVoterStatus::cases())->value,
            'pvc_status' => fake()->randomElement(PvcStatus::cases())->value,
            'preferred_language' => fake()->randomElement(PreferredLanguage::cases())->value,
            'preferred_language_other' => null,
            'preferred_channel' => fake()->randomElement(PreferredChannel::cases())->value,
            'preferred_channel_other' => null,
            'consent_to_contact' => true,
            'consent_to_data' => true,
            'captured_at' => now(),
        ];
    }
}
