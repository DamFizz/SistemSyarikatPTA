<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        Office::firstOrCreate(
            ['name' => 'HQ Penang'],
            [
                'address' => 'Bukit Mertajam, Seberang Perai, Penang',
                'latitude' => 5.355139,
                'longitude' => 100.206194,
                'allowed_radius_meters' => 150,
            ]
        );
    }
}
