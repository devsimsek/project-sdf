<?php

/**
 * Caching Configuration
 *
 * Supported drivers: file, redis, memcached
 *
 * @var array $config
 */
$config['cache'] = [
    'driver' => 'file',

    'file' => [
        'path' => sys_get_temp_dir() . '/sdf_cache/',
        'prefix' => 'sdf_cache_',
    ],

    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 2.5,
        'prefix' => 'sdf_cache:',
    ],

    'memcached' => [
        'host' => '127.0.0.1',
        'port' => 11211,
        'prefix' => 'sdf_cache_',
    ],
];
