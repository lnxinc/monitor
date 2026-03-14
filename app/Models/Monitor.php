<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\MonitorFactory> */
    use HasFactory;

    protected $appends = ['display_target'];

    protected $fillable = [
        'name',
        'type',
        'url',
        'config',
        'frequency_minutes',
        'status',
        'last_status_code',
        'last_response_time_ms',
        'last_checked_at',
        'failure_reason',
        'consecutive_failures',
        'ssl_valid_from',
        'ssl_expires_at',
        'ssl_issuer',
        'webhook_token',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'ssl_valid_from' => 'datetime',
            'ssl_expires_at' => 'datetime',
            'config' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $monitor) {
            if ($monitor->type === 'webhook' && empty($monitor->webhook_token)) {
                $monitor->webhook_token = (string) Str::uuid();
            }
            if ($monitor->type !== 'webhook') {
                $monitor->webhook_token = null;
            }
        });

        static::updating(function (self $monitor) {
            if ($monitor->type === 'webhook' && empty($monitor->webhook_token)) {
                $monitor->webhook_token = (string) Str::uuid();
            }
            if ($monitor->type !== 'webhook') {
                $monitor->webhook_token = null;
            }
        });
    }

    public function getDisplayTargetAttribute(): ?string
    {
        return $this->config['target'] ?? $this->url;
    }

    public function checks()
    {
        return $this->hasMany(MonitorCheck::class);
    }

    public function policies()
    {
        return $this->hasMany(MonitorPolicy::class);
    }
}
