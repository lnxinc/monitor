<?php

return [
    'bus' => [
        'dedupe_ttl_seconds' => env('ALERT_DEDUPE_TTL_SECONDS', 300),
        'flood' => [
            'limit' => env('ALERT_FLOOD_LIMIT', 10),
            'window_seconds' => env('ALERT_FLOOD_WINDOW_SECONDS', 60),
        ],
    ],
];
