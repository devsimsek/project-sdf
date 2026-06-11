<?php

declare(strict_types=1);

namespace SDF;

/**
 * smskSoft SDF Session
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Session.php
 * @version     v2.1.0
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

    /** @var bool Whether session_start() has been called */
    private bool $started = false;

    /** @var string|null Cache expiration override */
    private ?string $cacheExpire = null;

    /** @var string|null Cache limiter override */
    private ?string $cacheLimiter = null;

    /**
     * @param string|null $cacheExpire  Session cache expiration (minutes).
     * @param string|null $cacheLimiter Session cache limiter header.
     */
    final public function __construct(
        ?string $cacheExpire = null,
        ?string $cacheLimiter = null,
    ) {
        $this->cacheExpire = $cacheExpire;
        $this->cacheLimiter = $cacheLimiter;
    }

    /**
     * Retrieve the singleton instance (lazy - session_start on first data access).
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
     * Ensure the session has been started.
     *
     * @return void
     */
    private function ensureStarted(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            if ($this->cacheLimiter !== null) {
                session_cache_limiter($this->cacheLimiter);
            }

            if ($this->cacheExpire !== null) {
                session_cache_expire((int) $this->cacheExpire);
            }

            session_start();
        }

        $this->started = true;
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
        $this->ensureStarted();
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
        $this->ensureStarted();
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
        $this->ensureStarted();
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
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->ensureStarted();
        session_unset();
    }

    /**
     * Get the current session ID.
     *
     * @return string|false
     */
    public function id(): string|false
    {
        $this->ensureStarted();
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
        $this->ensureStarted();
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
        $this->ensureStarted();
        $_SESSION = [];
        session_destroy();
    }
}
