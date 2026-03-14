<?php

use App\Services\UnifiSiteManager;
use Carbon\Carbon;

it('parses millisecond epoch timestamps', function () {
    $svc = new UnifiSiteManager();
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod('parseTimestamp');
    $m->setAccessible(true);

    $ts = 1_700_000_000_000; // ms
    /** @var Carbon|null $dt */
    $dt = $m->invoke($svc, $ts);
    expect($dt)->toBeInstanceOf(Carbon::class);
    expect($dt->timestamp)->toBe((int) round($ts / 1000));
});

it('parses second epoch timestamps', function () {
    $svc = new UnifiSiteManager();
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod('parseTimestamp');
    $m->setAccessible(true);

    $ts = 1_700_000_000; // seconds
    /** @var Carbon|null $dt */
    $dt = $m->invoke($svc, $ts);
    expect($dt)->toBeInstanceOf(Carbon::class);
    expect($dt->timestamp)->toBe($ts);
});

it('parses ISO 8601 timestamps', function () {
    $svc = new UnifiSiteManager();
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod('parseTimestamp');
    $m->setAccessible(true);

    $iso = '2025-09-13T12:00:05Z';
    /** @var Carbon|null $dt */
    $dt = $m->invoke($svc, $iso);
    expect($dt)->toBeInstanceOf(Carbon::class);
    expect($dt->toIso8601String())->toBe('2025-09-13T12:00:05+00:00');
});

it('extracts last seen from common payload locations', function () {
    $svc = new UnifiSiteManager();
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod('extractLastSeenAt');
    $m->setAccessible(true);

    $raw = [
        'reportedState' => [
            'lastSeen' => 1_700_000_010,
            'controllers' => [
                ['name' => 'network', 'lastConnectionStateChange' => 1_700_000_005],
            ],
        ],
    ];

    /** @var Carbon|null $dt */
    $dt = $m->invoke($svc, $raw);
    expect($dt)->toBeInstanceOf(Carbon::class);
    expect($dt->timestamp)->toBe(1_700_000_010);
});

