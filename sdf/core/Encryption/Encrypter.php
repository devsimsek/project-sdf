<?php

/**
 * smskSoft SDF Encryption
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Encryption
 * @file        Encrypter.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @since       Version 2.2
 * @filesource
 */

namespace SDF\Encryption;

use RuntimeException;

/**
 * AES-256-CBC encrypt/decrypt for sensitive data.
 *
 * Usage:
 *   $enc = new Encrypter('base64-32-byte-key');
 *   $ciphertext = $enc->encrypt('secret data');
 *   $plaintext  = $enc->decrypt($ciphertext);
 */
class Encrypter
{
    private string $key;
    private string $cipher;

    private const HMAC_ALGO = 'sha256';
    private const HMAC_LEN = 32;

    /**
     * @param string      $key    Base64-encoded 32-byte key (or raw 32 bytes).
     * @param string|null $cipher OpenSSL cipher method (default: AES-256-CBC).
     */
    public function __construct(string $key, ?string $cipher = null)
    {
        if (strlen($key) === 44 && base64_encode(base64_decode($key, true)) === $key) {
            $key = base64_decode($key, true);
        }

        if (strlen($key) !== 32) {
            throw new RuntimeException('Encryption key must be 32 bytes (base64-encoded or raw).');
        }

        $this->key = $key;
        $this->cipher = strtolower($cipher ?? 'AES-256-CBC');

        if (!in_array($this->cipher, openssl_get_cipher_methods(), true)) {
            throw new RuntimeException("Unsupported cipher: {$this->cipher}");
        }
    }

    /**
     * Encrypt a value (encrypt-then-MAC).
     *
     * Format: base64( iv || ciphertext || hmac )
     *
     * @param string $value Plaintext.
     * @return string Base64-encoded ciphertext with HMAC integrity tag.
     */
    public function encrypt(string $value): string
    {
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);

        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed.');
        }

        $hmac = hash_hmac(self::HMAC_ALGO, $iv . $encrypted, $this->key, true);

        return base64_encode($iv . $encrypted . $hmac);
    }

    /**
     * Decrypt a value (verify-then-decrypt).
     *
     * @param string $payload Base64-encoded IV + ciphertext + HMAC.
     * @return string Plaintext.
     */
    public function decrypt(string $payload): string
    {
        $data = base64_decode($payload, true);
        if ($data === false) {
            throw new RuntimeException('Invalid encrypted payload (not valid base64).');
        }

        $ivLen = openssl_cipher_iv_length($this->cipher);

        if (strlen($data) < $ivLen + self::HMAC_LEN) {
            throw new RuntimeException('Invalid encrypted payload (too short).');
        }

        $iv = substr($data, 0, $ivLen);
        $encrypted = substr($data, $ivLen, -self::HMAC_LEN);
        $expectedMac = substr($data, -self::HMAC_LEN);

        $actualMac = hash_hmac(self::HMAC_ALGO, $iv . $encrypted, $this->key, true);

        if (!hash_equals($expectedMac, $actualMac)) {
            throw new RuntimeException('Decryption failed (MAC mismatch — data tampered or wrong key).');
        }

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed (corrupted data).');
        }

        return $decrypted;
    }

    /**
     * Resolve an encrypter instance from app/config/encryption.php.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        $config = \SDF\Core::coreGetConfig('encryption') ?: [];

        $key = $config['key'] ?? '';
        if ($key === '') {
            throw new RuntimeException('Encryption key not configured. Set APP_KEY in .env or config/encryption.php.');
        }

        return new self($key, $config['cipher'] ?? null);
    }
}
