<?php

namespace App\Http\Requests;

use App\Models\Monitor;
use App\Support\Monitoring\MonitorInputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $mutations = [];

        if (is_string($this->input('name'))) {
            $mutations['name'] = trim($this->input('name'));
        }

        if (is_string($this->input('url'))) {
            $mutations['url'] = trim($this->input('url'));
        }

        $config = $this->input('config');
        if (is_array($config)) {
            if (array_key_exists('method', $config) && is_string($config['method'])) {
                $config['method'] = strtoupper(trim($config['method']));
            }
            $mutations['config'] = $config;
        }

        if (! empty($mutations)) {
            $this->merge($mutations);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Monitor|null $monitor */
        $monitor = $this->route('monitor');
        $type = $this->input('type', $monitor?->type ?? 'http');

        $portRules = ['nullable', 'integer', 'between:1,65535'];
        if (in_array($type, ['tcp', 'ssl'], true)) {
            $portRules[0] = 'required';
        }

        $frequencyRules = ['integer', 'min:1', 'max:1440'];
        if ($type === 'webhook') {
            array_unshift($frequencyRules, 'nullable');
        } else {
            array_unshift($frequencyRules, 'required');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['http', 'ping', 'tcp', 'ssl', 'webhook', 'sip'])],
            'url' => ['required', 'string', 'max:2048'],
            'frequency_minutes' => $frequencyRules,
            'config' => ['nullable', 'array'],
            'config.host' => ['nullable', 'string', 'max:255'],
            'config.port' => $portRules,
            'config.timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
            'config.count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'config.method' => ['nullable', Rule::in(['HEAD', 'GET'])],
            'config.expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
        ];

        if ($type === 'http') {
            $rules['url'][] = 'url';
        }

        if ($type === 'sip') {
            $rules['config.phone_number'] = ['required', 'string', 'max:50'];
        }

        return $rules;
    }

    public function monitorAttributes(): array
    {
        /** @var Monitor|null $monitor */
        $monitor = $this->route('monitor');
        $validated = $this->validated();

        return MonitorInputSanitizer::buildAttributes($validated, $monitor);
    }
}
