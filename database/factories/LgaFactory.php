<?php

namespace Database\Factories;

use App\Models\Lga;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lga>
 */
class LgaFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' LGA';

        return [
            'state_id' => State::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => (string) fake()->unique()->numberBetween(1, 9999),
        ];
    }
}
