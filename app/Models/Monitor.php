<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\MonitorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
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
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'ssl_valid_from' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function checks()
    {
        return $this->hasMany(MonitorCheck::class);
    }
}

