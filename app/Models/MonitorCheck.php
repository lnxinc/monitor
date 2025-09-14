<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorCheck extends Model
{
    /** @use HasFactory<\Database\Factories\MonitorCheckFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'status',
        'status_code',
        'response_time_ms',
        'error',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}

