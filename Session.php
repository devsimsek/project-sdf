<?php

/**
 * smskSoft Session Library
 * This file is taken from the project spf (Closed Source)
 * Copyright smskSoft, mtnsmsk, devsimsek, Metin Şimşek.
 * @package     SDF Library Dist
 * @subpackage  Session
 * @file        Session.php
 * @version     v1.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2021, smskSoft, mtnsmsk
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/blob/libraries/Session.php
 * @since       Version 1.0
 * @filesource
 */
class Session extends SDF\Library
{

    public function __construct(?string $cacheExpire = null, ?string $cacheLimiter = null)
    {
        if (session_status() === PHP_SESSION_NONE) {

            if ($cacheLimiter !== null) {
                session_cache_limiter($cacheLimiter);
            }

            if ($cacheExpire !== null) {
                session_cache_expire($cacheExpire);
            }

            session_start();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Session
     */
    public function set(string $key, $value): Session
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     */
    public function remove(string $key): void
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function clear(): void
    {
        session_unset();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

}
