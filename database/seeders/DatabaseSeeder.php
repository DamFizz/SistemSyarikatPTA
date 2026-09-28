<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            OfficeSeeder::class,
            ShiftSeeder::class,
            LeaveTypeSeeder::class,
            TicketCategorySeeder::class,
            UserSeeder::class,
        ]);
    }
}
