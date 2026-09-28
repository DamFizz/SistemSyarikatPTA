<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin', 'hr_admin', 'manager');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
