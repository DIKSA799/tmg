<?php

namespace Database\Factories;

use App\Models\Ambassador;
use App\Models\NominationInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NominationInvitation>
 */
class NominationInvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nominee_id' => Ambassador::factory(),
            'inviter_id' => Ambassador::factory(),
            'senatorial_district_id' => fn (array $attributes) => Ambassador::query()
                ->whereKey($attributes['nominee_id'])
                ->value('senatorial_district_id'),
            'message' => null,
            'status' => NominationInvitation::STATUS_PENDING,
            'responded_at' => null,
        ];
    }
}
