<?php

namespace SDF\Auth;

/**
 * Project SDF Session Guard
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        SessionGuard.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Authenticate users via PHP sessions.
 *
 * Stores the authenticated user's ID in `$_SESSION['_auth_id']`
 * and retrieves the full model on subsequent requests.
 */
class SessionGuard implements Guard
{
    /** @var UserProvider User loader. */
    private UserProvider $provider;

    /** @var object|null Cached user instance. */
    private ?object $userInstance = null;

    /**
     * @param UserProvider $provider User loader instance.
     */
    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Determine if the current session has an authenticated user.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return object|null
     */
    public function user(): ?object
    {
        if ($this->userInstance !== null) {
            return $this->userInstance;
        }

        $id = $_SESSION['_auth_id'] ?? null;

        if ($id === null) {
            return null;
        }

        $this->userInstance = $this->provider->retrieveById($id);
        return $this->userInstance;
    }

    /**
     * Log a user into the session.
     *
     * @param object $user User model instance.
     * @return void
     */
    public function login(object $user): void
    {
        $this->userInstance = $user;
        $_SESSION['_auth_id'] = $user->id ?? null;
    }

    /**
     * Log the current user out.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->userInstance = null;
        unset($_SESSION['_auth_id']);
    }

    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @return bool True on success, false on failure.
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
}
