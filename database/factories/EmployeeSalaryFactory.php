<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    protected $model = EmployeeSalary::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'basic_salary' => 4200,
            'allowance' => 200,
            'epf_rate' => 11.00,
            'socso_rate' => 0.50,
            'eis_rate' => 0.20,
            'effective_date' => fake()->date(),
        ];
    }
}
