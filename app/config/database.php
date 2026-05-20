<?php

/**
 * Database Configuration
 * @var array $config
 */
$config["database"] = [
    "path" => "sqlite::memory:",
    "driver" => "sqlite",
];

// for mysql with PDO, the configuration should be like this:
// $config['database'] = [
//     'host' => '127.0.0.1',
//     'name' => 'sdf_app',
//     'user' => 'root',
//     'pass' => '',
//     'driver' => 'mysql'
// ];
