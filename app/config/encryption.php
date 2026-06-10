<?php

/**
 * Encryption Configuration
 *
 * @var array $config
 */
$config['encryption'] = [
    // AES-256-CBC key (base64-encoded, 32 bytes when decoded)
    // Generate: php -r "echo base64_encode(openssl_random_pseudo_bytes(32));"
    'key' => getenv('APP_KEY') ?: '',

    // Cipher method (openssl_get_cipher_methods() for all)
    'cipher' => 'AES-256-CBC',
];
