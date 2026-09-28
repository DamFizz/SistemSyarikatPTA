<?php

namespace App\Http\Requests\HRAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('hr_admin', 'super_admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
