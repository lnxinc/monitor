<?php

namespace App\Http\Requests\DeviceType;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:device_types,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:64'],
        ];
    }
}
