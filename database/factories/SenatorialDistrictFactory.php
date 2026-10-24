<?php

namespace Database\Factories;

use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SenatorialDistrict>
 */
class SenatorialDistrictFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' District';

        return [
            'state_id' => State::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
