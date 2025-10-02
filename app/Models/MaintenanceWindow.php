<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'ref_id',
        'start_at',
        'end_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        $now = now();
        return $this->start_at <= $now && $this->end_at >= $now;
    }
}

