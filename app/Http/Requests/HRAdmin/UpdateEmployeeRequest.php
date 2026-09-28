<?php

namespace App\Http\Requests\HRAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('hr_admin', 'super_admin');
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'role' => ['required', 'in:hr_admin,manager,technician,employee'],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'ic_number' => ['required', 'string', 'max:20', Rule::unique('employees', 'ic_number')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'dob' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['required', 'exists:departments,id'],
            'office_id' => ['required', 'exists:offices,id'],
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee->id])],
            'position' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'employment_status' => ['required', 'in:active,probation,resigned,terminated,suspended'],
            'join_date' => ['required', 'date'],
        ];
    }
}
