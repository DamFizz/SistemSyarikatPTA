<?php

namespace App\Http\Requests\HRAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('hr_admin', 'super_admin');
    }

    public function rules(): array
    {
        return [
            'period_name' => ['required', 'string', 'max:255', 'unique:payroll_periods,period_name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
