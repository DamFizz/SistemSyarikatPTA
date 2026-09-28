<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:offices,name'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'allowed_radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'nfc_tag_id' => ['nullable', 'string', 'max:255', 'unique:offices,nfc_tag_id'],
        ];
    }
}
