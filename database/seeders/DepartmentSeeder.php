<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Human Resources', 'Information Technology', 'Finance', 'Operations', 'Management'] as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
