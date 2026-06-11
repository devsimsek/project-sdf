<?php

/**
 * CORS Configuration
 *
 * @var array $config
 */
$config['cors'] = [
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'allow_credentials' => false,
];
