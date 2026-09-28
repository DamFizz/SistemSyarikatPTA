<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $office = Office::first();
        $morningShift = Shift::where('name', 'Morning Shift')->first();
        $hrDept = Department::where('name', 'Human Resources')->first();
        $itDept = Department::where('name', 'Information Technology')->first();

        // Super Admin — system-level account, no employee profile.
        User::firstOrCreate(
            ['email' => 'superadmin@sems.test'],
            ['name' => 'Amirul Hakim', 'password' => 'password', 'role' => User::ROLE_SUPER_ADMIN, 'email_verified_at' => now()]
        );

        $hrUser = User::firstOrCreate(
            ['email' => 'hradmin@sems.test'],
            ['name' => 'Siti Nurhaliza', 'password' => 'password', 'role' => User::ROLE_HR_ADMIN, 'email_verified_at' => now()]
        );
        $hrEmployee = $this->makeEmployee($hrUser, 'EMP001', 'HR Executive', $hrDept, $office, '900101-14-5501');
        $this->makeSalary($hrEmployee, 4500);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@sems.test'],
            ['name' => 'Rajesh Kumar', 'password' => 'password', 'role' => User::ROLE_MANAGER, 'email_verified_at' => now()]
        );
        $managerEmployee = $this->makeEmployee($managerUser, 'EMP002', 'IT Manager', $itDept, $office, '880202-10-5502');
        $this->makeSalary($managerEmployee, 6500);

        if ($itDept) {
            $itDept->update(['manager_id' => $managerEmployee->id]);
        }

        $technicianUser = User::firstOrCreate(
            ['email' => 'technician@sems.test'],
            ['name' => 'Chong Wei Ling', 'password' => 'password', 'role' => User::ROLE_TECHNICIAN, 'email_verified_at' => now()]
        );
        $technicianEmployee = $this->makeEmployee($technicianUser, 'EMP003', 'IT Support', $itDept, $office, '950303-08-5503', $managerEmployee->id);
        $this->makeSalary($technicianEmployee, 3200);

        $employeeUser = User::firstOrCreate(
            ['email' => 'employee@sems.test'],
            ['name' => 'Nur Aisyah', 'password' => 'password', 'role' => User::ROLE_EMPLOYEE, 'email_verified_at' => now()]
        );
        $employeeEmployee = $this->makeEmployee($employeeUser, 'EMP004', 'Software Developer', $itDept, $office, '970404-06-5504', $managerEmployee->id);
        $this->makeSalary($employeeEmployee, 4200);

        foreach (Employee::all() as $employee) {
            if ($morningShift) {
                EmployeeShift::firstOrCreate([
                    'employee_id' => $employee->id,
                    'shift_id' => $morningShift->id,
                ], ['effective_date' => $employee->join_date]);
            }

            foreach (LeaveType::all() as $leaveType) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => now()->year],
                    [
                        'allocated_days' => $leaveType->default_days_per_year,
                        'used_days' => 0,
                        'remaining_days' => $leaveType->default_days_per_year,
                    ]
                );
            }
        }
    }

    private function makeSalary(Employee $employee, float $basicSalary): void
    {
        EmployeeSalary::firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'basic_salary' => $basicSalary,
                'allowance' => 200,
                'epf_rate' => 11.00,
                'socso_rate' => 0.50,
                'eis_rate' => 0.20,
                'effective_date' => $employee->join_date,
            ]
        );
    }

    private function makeEmployee(User $user, string $code, string $position, ?Department $department, ?Office $office, string $ic, ?int $managerId = null): Employee
    {
        return Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'employee_code' => $code,
                'full_name' => $user->name,
                'ic_number' => $ic,
                'phone' => '01' . random_int(10000000, 99999999),
                'gender' => 'male',
                'dob' => now()->subYears(random_int(25, 45)),
                'department_id' => $department?->id,
                'office_id' => $office?->id,
                'manager_id' => $managerId,
                'position' => $position,
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'join_date' => now()->subYears(random_int(1, 5)),
            ]
        );
    }
}
