<?php

/**
 * SDF Encryption tests.
 *
 * @package     Tests
 * @file        EncryptionTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Encryption\Encrypter;

class EncryptionTest extends TestCase
{
    private Encrypter $encrypter;
    private string $rawKey;

    protected function setUp(): void
    {
        $this->rawKey = random_bytes(32);
        $this->encrypter = new Encrypter($this->rawKey);
    }

    public function test_encrypt_and_decrypt(): void
    {
        $plain = 'secret data';
        $encrypted = $this->encrypter->encrypt($plain);
        $this->assertNotSame($plain, $encrypted);
        $this->assertSame($plain, $this->encrypter->decrypt($encrypted));
    }

    public function test_encrypt_returns_base64(): void
    {
        $encrypted = $this->encrypter->encrypt('test');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);
    }

    public function test_different_ciphertexts_per_call(): void
    {
        $a = $this->encrypter->encrypt('same');
        $b = $this->encrypter->encrypt('same');
        $this->assertNotSame($a, $b);
    }

    public function test_decrypt_with_wrong_key_fails(): void
    {
        $encrypted = $this->encrypter->encrypt('secret');
        $wrong = new Encrypter(random_bytes(32));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $wrong->decrypt($encrypted);
    }

    public function test_decrypt_invalid_payload(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid encrypted payload');
        $this->encrypter->decrypt('not-valid-base64!!!');
    }

    public function test_base64_encoded_key(): void
    {
        $b64 = base64_encode($this->rawKey);
        $enc = new Encrypter($b64);
        $cipher = $enc->encrypt('test');
        $this->assertSame('test', $enc->decrypt($cipher));
    }

    public function test_short_key_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('32 bytes');
        new Encrypter('too-short');
    }

    public function test_encrypts_empty_string(): void
    {
        $encrypted = $this->encrypter->encrypt('');
        $this->assertSame('', $this->encrypter->decrypt($encrypted));
    }

    public function test_encrypts_unicode(): void
    {
        $plain = 'héllo wörld 🔐';
        $encrypted = $this->encrypter->encrypt($plain);
        $this->assertSame($plain, $this->encrypter->decrypt($encrypted));
    }

    public function test_fromConfig_throws_when_key_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Encryption key not configured');
        Encrypter::fromConfig();
    }
}
