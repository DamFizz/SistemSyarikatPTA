<?php

namespace App\Http\Requests\HRAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('hr_admin', 'super_admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($this->route('department'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('department_id', $this->route('department')->id)],
        ];
    }
}
