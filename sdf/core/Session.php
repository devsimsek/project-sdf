<?php

namespace SDF;

/**
 * Project SDF Session
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Session.php
 * @version     v2.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/session
 * @since       Version 1.0
 * @filesource
 */
class Session
{
    /** @var self|null Singleton instance */
    private static ?Session $instance = null;

    /**
     * Start or resume a session.
     *
     * @param string|null $cacheExpire  Session cache expiration (minutes).
     * @param string|null $cacheLimiter Session cache limiter header.
     */
    public function __construct(
        ?string $cacheExpire = null,
        ?string $cacheLimiter = null,
    ) {
        if (session_status() === PHP_SESSION_NONE) {
            if ($cacheLimiter !== null) {
                session_cache_limiter($cacheLimiter);
            }

            if ($cacheExpire !== null) {
                session_cache_expire((int) $cacheExpire);
            }

            session_start();
        }
    }

    /**
     * Retrieve the singleton instance.
     *
     * @param string|null $cacheExpire  Session cache expiration (minutes).
     * @param string|null $cacheLimiter Session cache limiter header.
     * @return self
     */
    public static function getInstance(
        ?string $cacheExpire = null,
        ?string $cacheLimiter = null,
    ): self {
        if (self::$instance === null) {
            self::$instance = new self($cacheExpire, $cacheLimiter);
        }

        return self::$instance;
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    /**
     * Store a value in the session.
     *
     * @param string $key   Session key.
     * @param mixed  $value Value to store.
     * @return $this
     */
    public function set(string $key, mixed $value): self
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key Session key.
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Remove a value from the session.
     *
     * @param string $key Session key.
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data.
     *
     * @return void
     */
    public function clear(): void
    {
        session_unset();
    }

    /**
     * Get the current session ID.
     *
     * @return string|false
     */
    public function id(): string|false
    {
        return session_id();
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $deleteOld Whether to delete the old session data.
     * @return $this
     */
    public function regenerate(bool $deleteOld = false): self
    {
        session_regenerate_id($deleteOld);
        return $this;
    }

    /**
     * Destroy the session and its data.
     *
     * @return void
     */
    public function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
