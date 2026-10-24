<?php

namespace Database\Factories;

use App\Models\Ambassador;
use App\Models\Nomination;
use App\Models\NominationVote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NominationVote>
 */
class NominationVoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomination_id' => Nomination::factory(),
            'voter_id' => Ambassador::factory(),
            'senatorial_district_id' => fn (array $attributes) => Nomination::query()
                ->whereKey($attributes['nomination_id'])
                ->value('senatorial_district_id'),
        ];
    }
}
