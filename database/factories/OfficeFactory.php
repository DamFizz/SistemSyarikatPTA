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
            'wifi_ssid' => 'SEMS-Office',
            'wifi_password' => 'office-secret',
            'wifi_security' => 'WPA',
            'allowed_ips' => '127.0.0.1',
            'network_check_enabled' => true,
        ];
    }
}
