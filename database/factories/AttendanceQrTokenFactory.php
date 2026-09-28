<?php

namespace Database\Factories;

use App\Models\AttendanceQrToken;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttendanceQrToken>
 */
class AttendanceQrTokenFactory extends Factory
{
    protected $model = AttendanceQrToken::class;

    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'checkpoint_name' => 'Main Entrance',
            'token' => Str::random(40),
            'expires_at' => now()->addMinute(),
            'is_used' => false,
        ];
    }
}
