<?php

namespace Database\Factories;

use App\Models\Ambassador;
use App\Models\VoterRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ambassador>
 */
class AmbassadorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voter_record_id' => VoterRecord::factory(),
            'name' => fake()->name(),
            'phone' => '+234803'.fake()->unique()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            // The TMG number doubles as the login password.
            'password' => fn (array $attributes) => VoterRecord::query()
                ->whereKey($attributes['voter_record_id'])
                ->value('reference'),
            'photo_path' => null,
            'state_id' => fn (array $attributes) => VoterRecord::query()
                ->whereKey($attributes['voter_record_id'])
                ->value('state_id'),
            'lga_id' => fn (array $attributes) => VoterRecord::query()
                ->whereKey($attributes['voter_record_id'])
                ->value('lga_id'),
            'ward_id' => fn (array $attributes) => VoterRecord::query()
                ->whereKey($attributes['voter_record_id'])
                ->value('ward_id'),
            'polling_unit_id' => fn (array $attributes) => VoterRecord::query()
                ->whereKey($attributes['voter_record_id'])
                ->value('polling_unit_id'),
            'senatorial_district_id' => null,
            'terms_accepted_at' => null,
            'notice_seen_at' => null,
            'last_login_at' => null,
        ];
    }
}
