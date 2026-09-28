<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Annual Leave', 'default_days_per_year' => 14, 'requires_attachment' => false],
            ['name' => 'Medical Leave', 'default_days_per_year' => 14, 'requires_attachment' => true],
            ['name' => 'Emergency Leave', 'default_days_per_year' => 3, 'requires_attachment' => false],
            ['name' => 'Unpaid Leave', 'default_days_per_year' => 0, 'requires_attachment' => false],
            ['name' => 'Other Leave', 'default_days_per_year' => 0, 'requires_attachment' => false],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
