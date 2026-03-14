<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deviceId = $this->route('device')?->id;

        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                "unique:devices,name,{$deviceId},id,organization_id,".$this->organization_id,
            ],
            'ip_address' => ['required', 'ip'],
            'status' => ['sometimes', Rule::enum(DeviceStatus::class)],
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
