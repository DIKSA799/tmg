<?php

namespace Database\Factories;

use App\Models\Ambassador;
use App\Models\Nomination;
use App\Models\SenatorialDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nomination>
 */
class NominationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ambassador_id' => Ambassador::factory(),
            'state_id' => fn (array $attributes) => Ambassador::query()
                ->whereKey($attributes['ambassador_id'])
                ->value('state_id'),
            'senatorial_district_id' => function (array $attributes): int {
                $ambassador = Ambassador::query()->findOrFail($attributes['ambassador_id']);

                return $ambassador->senatorial_district_id
                    ?? SenatorialDistrict::factory()->for($ambassador->state)->create()->id;
            },
            'nominated_by' => fn (array $attributes) => $attributes['ambassador_id'],
            'statement' => null,
            'status' => Nomination::STATUS_ACTIVE,
            'votes_count' => 0,
            'accepted_at' => now(),
        ];
    }
}
