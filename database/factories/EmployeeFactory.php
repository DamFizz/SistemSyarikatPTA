<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_code' => 'EMP'.fake()->unique()->numberBetween(1000, 9999),
            'full_name' => fake()->name(),
            'ic_number' => fake()->unique()->numerify('######-##-####'),
            'phone' => fake()->numerify('01#########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'dob' => fake()->date(),
            'department_id' => Department::factory(),
            'office_id' => Office::factory(),
            'position' => fake()->jobTitle(),
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'join_date' => fake()->date(),
        ];
    }
}
