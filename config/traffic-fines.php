<?php

use App\Features\TrafficFine\Services\Source\Xephatnguoi\XephatnguoiSource;

return [
    'default_source' => env('TRAFFIC_FINE_SOURCE', env('TRAFFIC_FINE_PROVIDER', 'xephatnguoi')),

    'sources' => [
        'xephatnguoi' => [
            'driver' => XephatnguoiSource::class,
            'priority' => 1,
            'url' => env('XEPHATNGUOI_API_URL', env('TRAFFIC_FINE_API_URL')),
            'token' => env('XEPHATNGUOI_API_TOKEN', env('TRAFFIC_FINE_API_TOKEN')),
            'timeout' => (int) env('XEPHATNGUOI_TIMEOUT', env('TRAFFIC_FINE_TIMEOUT', 10)),
            'connect_timeout' => (int) env('XEPHATNGUOI_CONNECT_TIMEOUT', env('TRAFFIC_FINE_CONNECT_TIMEOUT', 3)),
            'retry_times' => (int) env('XEPHATNGUOI_RETRY_TIMES', env('TRAFFIC_FINE_RETRY_TIMES', 2)),
            'retry_sleep_ms' => (int) env('XEPHATNGUOI_RETRY_SLEEP_MS', env('TRAFFIC_FINE_RETRY_SLEEP_MS', 200)),
            'vehicle_types' => [
                'car' => 1,
                'motorbike' => 2,
                'electric_motorbike' => 3,
            ],
        ],
    ],

    'cache' => [
        'store' => env('TRAFFIC_FINE_CACHE_STORE', 'redis'),
        'ttl' => (int) env('TRAFFIC_FINE_CACHE_TTL', 86400),
        'error_ttl' => (int) env('TRAFFIC_FINE_ERROR_CACHE_TTL', 60),
        'lock_seconds' => (int) env('TRAFFIC_FINE_LOCK_SECONDS', 15),
        'lock_wait_seconds' => (int) env('TRAFFIC_FINE_LOCK_WAIT_SECONDS', 3),
    ],

    'rate_limit' => [
        'per_minute' => (int) env('TRAFFIC_FINE_RATE_LIMIT_PER_MINUTE', 20),
    ],

    'billing' => [
        'api_request_price' => (int) env('TRAFFIC_FINE_API_REQUEST_PRICE', 20),
    ],

    'plate_pattern' => env('TRAFFIC_FINE_PLATE_PATTERN', '/^\d{2}[A-ZĐ]{1,2}\d{4,6}$/u'),

    'vehicle_types' => [
        'car' => ['label' => 'Ô tô', 'enabled' => true],
        'motorbike' => ['label' => 'Xe máy', 'enabled' => true],
        'electric_motorbike' => ['label' => 'Xe máy điện', 'enabled' => true],
    ],

];
