<?php

namespace SDF\Auth;

use SDF\Core;
use SDF\Request;

/**
 * Project SDF Auth Facade
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        Auth.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Static facade over the active authentication guard.
 *
 * Usage:
 * ```php
 * use SDF\Auth\Auth;
 *
 * if (Auth::check()) {
 *     $user = Auth::user();
 * }
 * Auth::attempt(['email' => $email, 'password' => $pw]);
 * Auth::logout();
 * ```
 *
 * Configuration is read from `app/config/auth.php` (key: `auth`).
 */
class Auth
{
    /** @var array<string, Guard> Resolved guard instances. */
    private static array $guards = [];

    /**
     * Get a guard instance by name.
     *
     * @param string|null $name Guard name (defaults to config default).
     * @return Guard
     */
    public static function guard(?string $name = null): Guard
    {
        $name ??= self::getDefaultGuard();

        if (!isset(self::$guards[$name])) {
            self::$guards[$name] = self::resolveGuard($name);
        }

        return self::$guards[$name];
    }

    /**
     * Determine if the current request has an authenticated user.
     *
     * @return bool
     */
    public static function check(): bool
    {
        return self::guard()->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return object|null
     */
    public static function user(): ?object
    {
        return self::guard()->user();
    }

    /**
     * Log a user in using the default guard.
     *
     * @param object $user User model instance.
     * @return void
     */
    public static function login(object $user): void
    {
        self::guard()->login($user);
    }

    /**
     * Log the current user out.
     *
     * @return void
     */
    public static function logout(): void
    {
        self::guard()->logout();
    }

    /**
     * Attempt to authenticate with credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @return bool
     */
    public static function attempt(array $credentials): bool
    {
        return self::guard()->attempt($credentials);
    }

    /**
     * Issue an access token (JWT guard only).
     *
     * @param object $user User model instance.
     * @return string|null Encoded JWT or null if guard is not JwtGuard.
     */
    public static function issueToken(object $user): ?string
    {
        $guard = self::guard();

        if ($guard instanceof JwtGuard) {
            return $guard->issueToken($user);
        }

        return null;
    }

    /**
     * Refresh an access token using a refresh token (JWT guard only).
     *
     * @param string $refreshToken A valid refresh JWT.
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}|null
     */
    public static function refresh(string $refreshToken): ?array
    {
        $guard = self::guard();

        if ($guard instanceof JwtGuard) {
            return $guard->refresh($refreshToken);
        }

        return null;
    }

    /**
     * Replace a resolved guard (useful in tests).
     *
     * @param string $name  Guard name.
     * @param Guard  $guard Guard instance.
     * @return void
     */
    public static function setGuard(string $name, Guard $guard): void
    {
        self::$guards[$name] = $guard;
    }

    /**
     * Reset all resolved guards (useful in tests).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$guards = [];
    }

    /**
     * Get the default guard name from config.
     *
     * @return string
     */
    private static function getDefaultGuard(): string
    {
        $config = Core::coreGetConfig('auth') ?: [];
        return $config['default'] ?? 'session';
    }

    /**
     * Resolve and construct a guard from config.
     *
     * @param string $name Guard name.
     * @return Guard
     * @throws \RuntimeException If the guard is not configured.
     */
    private static function resolveGuard(string $name): Guard
    {
        $config = Core::coreGetConfig('auth') ?: [];
        $guardConfig = $config['guards'][$name] ?? [];

        if (empty($guardConfig)) {
            throw new \RuntimeException("Auth guard '$name' is not configured.");
        }

        $providerName = $guardConfig['provider'] ?? 'users';
        $providerConfig = $config['providers'][$providerName] ?? [];

        $model = $providerConfig['model'] ?? \App\Models\User::class;
        $provider = new UserProvider($model);

        return match ($name) {
            'jwt' => new JwtGuard(
                $provider,
                new Request(),
                $guardConfig['secret'] ?? '',
                (int) ($guardConfig['ttl'] ?? 3600),
                (int) ($guardConfig['refresh_ttl'] ?? 604800),
            ),
            default => new SessionGuard($provider),
        };
    }
}
