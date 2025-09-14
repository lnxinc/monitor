<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'status' => ['sometimes', 'in:online,offline,warning,critical'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organization is required.',
            'organization_id.exists' => 'Selected organization does not exist.',
            'name.required' => 'Device name is required.',
            'name.max' => 'Device name cannot exceed 255 characters.',
            'ip_address.required' => 'IP address is required.',
            'ip_address.ip' => 'IP address must be a valid IP address.',
            'status.in' => 'Status must be one of: online, offline, warning, critical.',
        ];
    }
}
