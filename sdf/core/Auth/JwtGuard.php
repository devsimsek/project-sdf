<?php

namespace SDF\Auth;

use SDF\Request;

/**
 * Project SDF JWT Guard
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        JwtGuard.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Authenticate users via stateless JWT bearer tokens.
 *
 * Implements HS256 JWT (HMAC-SHA256) with access + refresh token support.
 * Tokens are read from the `Authorization: Bearer` header.
 */
class JwtGuard implements Guard
{
    private UserProvider $provider;
    private Request $request;
    private string $secret;
    private int $ttl;
    private int $refreshTtl;

    private ?object $userInstance = null;

    /**
     * @param UserProvider $provider   User loader instance.
     * @param Request      $request    Current request (for bearer token extraction).
     * @param string       $secret     HMAC secret key.
     * @param int          $ttl        Access token TTL in seconds.
     * @param int          $refreshTtl Refresh token TTL in seconds.
     */
    public function __construct(
        UserProvider $provider,
        Request $request,
        string $secret,
        int $ttl = 3600,
        int $refreshTtl = 604800,
    ) {
        if ($secret === '') {
            throw new \RuntimeException('JWT guard: secret must not be empty. Set JWT_SECRET in .env or config/auth.php.');
        }
        $this->provider = $provider;
        $this->request = $request;
        $this->secret = $secret;
        $this->ttl = $ttl;
        $this->refreshTtl = $refreshTtl;
    }

    /**
     * Determine if a valid bearer token is present.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the authenticated user from the bearer token.
     *
     * @return object|null
     */
    public function user(): ?object
    {
        if ($this->userInstance !== null) {
            return $this->userInstance;
        }

        $token = $this->request->bearerToken();

        if ($token === null) {
            return null;
        }

        $payload = $this->decode($token);

        if ($payload === null || ($payload->type ?? 'access') !== 'access') {
            return null;
        }

        $this->userInstance = $this->provider->retrieveById($payload->sub);
        return $this->userInstance;
    }

    /**
     * Set the authenticated user (does not issue a token).
     *
     * @param object $user User model instance.
     * @return void
     */
    public function login(object $user): void
    {
        $this->userInstance = $user;
    }

    /**
     * Clear the authenticated user.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->userInstance = null;
    }

    /**
     * Attempt to authenticate with credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @return bool
     */
    public function attempt(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        if (!$this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user);
        return true;
    }

    /**
     * Encode a payload into a signed JWT.
     *
     * @param array $payload Token claims.
     * @return string Encoded JWT string.
     */
    public function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [];

        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode and verify a JWT. Returns null on any failure.
     *
     * @param string $token Encoded JWT string.
     * @return object|null Decoded payload or null.
     */
    public function decode(string $token): ?object
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $signingInput = "$headerB64.$payloadB64";
        $signature = $this->base64UrlDecode($signatureB64);

        if ($signature === false) {
            return null;
        }

        if (!$this->verify($signingInput, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64));

        if (!$payload || !isset($payload->exp) || !isset($payload->sub)) {
            return null;
        }

        if ($payload->exp < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Issue a signed access token for the given user.
     *
     * @param object $user User model instance.
     * @return string Encoded JWT.
     */
    public function issueToken(object $user): string
    {
        return $this->encode([
            'sub' => $user->id ?? null,
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'type' => 'access',
        ]);
    }

    /**
     * Issue a signed refresh token for the given user.
     *
     * @param object $user User model instance.
     * @return string Encoded JWT.
     */
    public function issueRefreshToken(object $user): string
    {
        return $this->encode([
            'sub' => $user->id ?? null,
            'iat' => time(),
            'exp' => time() + $this->refreshTtl,
            'type' => 'refresh',
        ]);
    }

    /**
     * Attempt to refresh an access token using a refresh token.
     *
     * @param string $refreshToken A valid refresh JWT.
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}|null
     */
    public function refresh(string $refreshToken): ?array
    {
        $payload = $this->decode($refreshToken);

        if ($payload === null || ($payload->type ?? '') !== 'refresh') {
            return null;
        }

        $user = $this->provider->retrieveById($payload->sub);

        if ($user === null) {
            return null;
        }

        return [
            'access_token' => $this->issueToken($user),
            'refresh_token' => $this->issueRefreshToken($user),
            'token_type' => 'Bearer',
            'expires_in' => $this->ttl,
        ];
    }

    /**
     * Sign an input string using HMAC-SHA256.
     *
     * @param string $input Data to sign.
     * @return string Raw binary signature.
     */
    private function sign(string $input): string
    {
        return hash_hmac('sha256', $input, $this->secret, true);
    }

    /**
     * Verify a signature against an input string using timing-safe comparison.
     *
     * @param string $input     Original data.
     * @param string $signature Signature to verify.
     * @return bool
     */
    private function verify(string $input, string $signature): bool
    {
        $expected = $this->sign($input);

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $signature);
        }

        return $expected === $signature;
    }

    /**
     * Base64URL encode (RFC 4648 section 5, no padding).
     *
     * @param string $data Raw data.
     * @return string URL-safe base64 string.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode.
     *
     * @param string $data URL-safe base64 string.
     * @return string|false Decoded data or false on failure.
     */
    private function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}
