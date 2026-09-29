<?php

namespace Database\Factories;

use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PollingUnit>
 */
class PollingUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ward_id' => Ward::factory(),
            'name' => fake()->unique()->company().' Polling Unit',
            'code' => fake()->unique()->numerify('##/##/##/###'),
            'pu_code' => fake()->numerify('###'),
        ];
    }
}
