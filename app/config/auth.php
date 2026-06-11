<?php

$config['auth'] = [
    'default' => 'session',
    'guards' => [
        'session' => [
            'provider' => 'users',
        ],
        'jwt' => [
            'provider' => 'users',
            'secret' => env('JWT_SECRET', ''),
            'ttl' => 3600,
            'refresh_ttl' => 604800,
            'algo' => 'HS256',
        ],
    ],
    'providers' => [
        'users' => [
            'model' => \App\Models\User::class,
        ],
    ],
];
