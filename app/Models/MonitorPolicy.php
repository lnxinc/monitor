<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'down_threshold',
        'notify_after_seconds',
        'repeat_interval_minutes',
        'notify_on_recovery',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'notify_on_recovery' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function channels()
    {
        return $this->belongsToMany(NotificationChannel::class, 'monitor_policy_channel', 'monitor_policy_id', 'notification_channel_id');
    }
}

