<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Warning = 'warning';
    case Critical = 'critical';
}
