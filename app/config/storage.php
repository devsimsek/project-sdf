<?php

$config['storage'] = [
    'default' => 'local',
    'disks' => [
        'local' => [
            'root' => __DIR__ . '/../storage',
            'url' => '/storage',
        ],
        's3' => [
            'driver' => 's3',
            'key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
            'secret' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
            'region' => getenv('AWS_DEFAULT_REGION') ?: 'us-east-1',
            'bucket' => getenv('AWS_BUCKET') ?: '',
            'endpoint' => getenv('AWS_ENDPOINT') ?: '',
        ],
    ],
];
