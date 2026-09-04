<?php

return [
    'default' => env('CACHE_STORE', 'redis'),
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        'database' => ['driver' => 'database', 'connection' => null, 'table' => 'cache', 'lock_connection' => null, 'lock_table' => 'cache_locks'],
        'redis' => ['driver' => 'redis', 'connection' => 'cache', 'lock_connection' => 'default'],
    ],
    'prefix' => env('CACHE_PREFIX', 'jordan_hr_cache_'),
];
