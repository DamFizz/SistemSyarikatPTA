<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Aina Sofea',
            'email' => 'aina@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'employee_code' => 'EMP0500',
            'ic_number' => '990101-14-5566',
            'phone' => '0123456789',
            'gender' => 'female',
            'dob' => '1999-01-01',
            'address' => 'Kuala Lumpur',
            'department_id' => Department::factory()->create()->id,
            'office_id' => Office::factory()->create()->id,
            'manager_id' => '',
            'position' => 'Clerk',
            'employment_type' => 'full_time',
            'employment_status' => 'probation',
            'join_date' => now()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_hr_admin_can_create_an_employee(): void
    {
        LeaveType::factory()->create();
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        $this->actingAs($hr)->post(route('hr.employees.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('hr.employees.index'));

        $employee = Employee::where('employee_code', 'EMP0500')->firstOrFail();
        $this->assertSame('aina@example.com', $employee->user->email);
        $this->assertSame(1, $employee->leaveBalances()->count());
    }

    public function test_super_admin_can_create_an_employee(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->post(route('hr.employees.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('hr.employees.index'));

        $this->assertDatabaseHas('users', ['email' => 'aina@example.com']);
    }
}
