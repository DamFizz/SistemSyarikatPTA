<?php

namespace App\Http\Requests\HRAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('hr_admin', 'super_admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:hr_admin,manager,technician,employee'],
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'ic_number' => ['required', 'string', 'max:20', 'unique:employees,ic_number'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'dob' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['required', 'exists:departments,id'],
            'office_id' => ['required', 'exists:offices,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'position' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'employment_status' => ['required', 'in:active,probation,resigned,terminated,suspended'],
            'join_date' => ['required', 'date'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'allowance' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ic_number' => 'IC number',
            'employee_code' => 'employee code',
            'department_id' => 'department',
            'office_id' => 'office',
            'manager_id' => 'reporting manager',
            'shift_id' => 'work shift',
            'dob' => 'date of birth',
        ];
    }
}
