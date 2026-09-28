<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->employee !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
