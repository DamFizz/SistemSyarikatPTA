<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Office>
 */
class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Office',
            'address' => fake()->address(),
            'latitude' => 3.1579,
            'longitude' => 101.7116,
            'allowed_radius_meters' => 150,
        ];
    }
}
