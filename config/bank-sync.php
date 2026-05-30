<?php

return [
    'providers' => [
        'acb' => [
            'requests_per_minute' => 6,
            'cooldown_seconds' => 10,
            'lock_seconds' => 20,
        ],
        'mb' => [
            'requests_per_minute' => 10,
            'cooldown_seconds' => 6,
            'lock_seconds' => 15,
        ],
        'vcb' => [
            'requests_per_minute' => 6,
            'cooldown_seconds' => 10,
            'lock_seconds' => 20,
        ],
    ],
];
