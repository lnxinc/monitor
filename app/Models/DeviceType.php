<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
