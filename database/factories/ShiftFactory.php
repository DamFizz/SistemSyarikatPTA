<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 10,
            'break_duration_minutes' => 60,
        ];
    }
}
