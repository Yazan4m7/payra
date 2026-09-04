<?php
return [
    'defaults' => ['guard' => 'web', 'passwords' => 'users'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'central' => ['driver' => 'session', 'provider' => 'central_users'],
    ],
    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
        'central_users' => ['driver' => 'eloquent', 'model' => App\Models\CentralUser::class],
    ],
    'passwords' => [
        'users' => ['provider' => 'users', 'table' => 'password_reset_tokens', 'expire' => 60, 'throttle' => 60],
    ],
    'password_timeout' => 10800,
];
