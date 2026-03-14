<?php

return [
    'heartbeat' => [
        'max_attempts' => env('HEARTBEAT_MAX_ATTEMPTS', 3),
        'initial_backoff_ms' => env('HEARTBEAT_INITIAL_BACKOFF_MS', 200),
        'backoff_multiplier' => env('HEARTBEAT_BACKOFF_MULTIPLIER', 2.0),
    ],

    'http' => [
        'user_agent' => env('MONITOR_HTTP_USER_AGENT', 'MSP-Monitor/2.0'),
    ],
];
