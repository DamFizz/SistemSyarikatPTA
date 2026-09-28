<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->employee !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
