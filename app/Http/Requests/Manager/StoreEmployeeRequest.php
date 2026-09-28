<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('manager');
    }

    /**
     * Managers may only create employee-tier accounts within their own department —
     * system role and department are intentionally not accepted from this form to
     * prevent a manager from escalating an account to hr_admin/super_admin/manager.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'ic_number' => ['required', 'string', 'max:20', 'unique:employees,ic_number'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'dob' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'office_id' => ['required', 'exists:offices,id'],
            'position' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'join_date' => ['required', 'date'],
        ];
    }
}
