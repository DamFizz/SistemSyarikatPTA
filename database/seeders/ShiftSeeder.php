<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['name' => 'Morning Shift', 'start_time' => '08:00', 'end_time' => '17:00', 'grace_period_minutes' => 10, 'break_duration_minutes' => 60],
            ['name' => 'Evening Shift', 'start_time' => '14:00', 'end_time' => '23:00', 'grace_period_minutes' => 10, 'break_duration_minutes' => 60],
            ['name' => 'Night Shift', 'start_time' => '22:00', 'end_time' => '07:00', 'grace_period_minutes' => 10, 'break_duration_minutes' => 60],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(['name' => $shift['name']], $shift);
        }
    }
}
