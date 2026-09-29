<?php

namespace Database\Factories;

use App\Models\Lga;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->streetName().' Ward';

        return [
            'lga_id' => Lga::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => (string) fake()->unique()->numberBetween(1, 9999),
        ];
    }
}
