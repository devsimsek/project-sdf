<?php

namespace SDF;

/**
 * Project SDF Flash Messages
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Flash.php
 * @version     v2.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/flash
 * @since       Version 2.0
 * @filesource
 */
class Flash
{
    /** @var string Session key for new (next-request) flash messages */
    private const NEW_KEY = '_sdf_flash_new';

    /** @var string Session key for current-request flash messages */
    private const CUR_KEY = '_sdf_flash_cur';

    /** @var Session Backing session store */
    private Session $session;

    /**
     * @param Session|null $session Optional session instance; defaults to singleton.
     */
    public function __construct(?Session $session = null)
    {
        $this->session = $session ?? Session::getInstance();
        $this->age();
    }

    /**
     * Set a flash message for the next request.
     *
     * @param string $key   Message key.
     * @param mixed  $value Message value.
     * @return $this
     */
    public function set(string $key, mixed $value): self
    {
        $_SESSION[self::NEW_KEY][$key] = $value;
        return $this;
    }

    /**
     * Retrieve a flash message (consumes it).
     *
     * @param string $key     Message key.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($_SESSION[self::CUR_KEY][$key])) {
            $value = $_SESSION[self::CUR_KEY][$key];
            unset($_SESSION[self::CUR_KEY][$key]);
            return $value;
        }

        return $default;
    }

    /**
     * Check if a flash message exists (does not consume).
     *
     * @param string $key Message key.
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[self::CUR_KEY][$key])
            || isset($_SESSION[self::NEW_KEY][$key]);
    }

    /**
     * Return all flash messages without consuming them.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge(
            $_SESSION[self::NEW_KEY] ?? [],
            $_SESSION[self::CUR_KEY] ?? [],
        );
    }

    /**
     * Keep a flash message for one more request.
     *
     * @param string $key Message key.
     * @return $this
     */
    public function keep(string $key): self
    {
        if (isset($_SESSION[self::CUR_KEY][$key])) {
            $_SESSION[self::NEW_KEY][$key] = $_SESSION[self::CUR_KEY][$key];
            unset($_SESSION[self::CUR_KEY][$key]);
        }

        return $this;
    }

    /**
     * Set a flash message available immediately (does not persist).
     *
     * @param string $key   Message key.
     * @param mixed  $value Message value.
     * @return $this
     */
    public function now(string $key, mixed $value): self
    {
        $_SESSION[self::CUR_KEY][$key] = $value;
        return $this;
    }

    /**
     * Alias for set().
     *
     * @param string $key   Message key.
     * @param mixed  $value Message value.
     * @return $this
     */
    public function flash(string $key, mixed $value): self
    {
        return $this->set($key, $value);
    }

    /**
     * Age flash data: discard current-request messages and promote
     * next-request messages to the current request.
     *
     * @return void
     */
    private function age(): void
    {
        if (isset($_SESSION[self::CUR_KEY])) {
            unset($_SESSION[self::CUR_KEY]);
        }

        if (isset($_SESSION[self::NEW_KEY])) {
            $_SESSION[self::CUR_KEY] = $_SESSION[self::NEW_KEY];
            unset($_SESSION[self::NEW_KEY]);
        }
    }
}
