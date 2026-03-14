<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Monitor */
class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'url' => $this->url,
            'display_target' => $this->display_target,
            'config' => $this->config ?? [],
            'frequency_minutes' => $this->frequency_minutes,
            'status' => $this->status,
            'last_status_code' => $this->last_status_code,
            'last_response_time_ms' => $this->last_response_time_ms,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'failure_reason' => $this->failure_reason,
            'consecutive_failures' => $this->consecutive_failures,
            'webhook_token' => $this->webhook_token,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'policies' => $this->whenLoaded('policies', function () {
                return $this->policies->map(function ($policy) {
                    return [
                        'id' => $policy->id,
                        'down_threshold' => $policy->down_threshold,
                        'notify_after_seconds' => $policy->notify_after_seconds,
                        'repeat_interval_minutes' => $policy->repeat_interval_minutes,
                        'notify_on_recovery' => $policy->notify_on_recovery,
                        'enabled' => $policy->enabled,
                        'channels' => $policy->channels->map(function ($channel) {
                            return [
                                'id' => $channel->id,
                                'name' => $channel->name,
                                'type' => $channel->type,
                            ];
                        })->values(),
                    ];
                })->values();
            }),
        ];
    }
}
