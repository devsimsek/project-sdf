<?php

/**
 * Project SDF
 * devsimsek software development framework.
 * Copyright devsimsek
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Mail Configuration
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Configure your mail driver and settings.
 * @var array $config
 */

$config['mail'] = [
    'default' => 'log',
    'from' => ['address' => 'hello@example.com', 'name' => 'SDF'],
    'smtp' => [
        'host' => getenv('MAIL_HOST') ?: 'localhost',
        'port' => (int)(getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    ],
    'log' => [
        'path' => sys_get_temp_dir() . '/sdf_mail.log',
    ],
];
