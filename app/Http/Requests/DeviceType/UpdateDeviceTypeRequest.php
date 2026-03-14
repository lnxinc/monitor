<?php

namespace App\Http\Requests\DeviceType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Resource route parameter is typically snake_case: 'device_type'
        $type = $this->route('device_type') ?? $this->route('deviceType');
        $typeId = is_object($type) ? ($type->id ?? null) : $type;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('device_types', 'name')->ignore($typeId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:64'],
        ];
    }
}
