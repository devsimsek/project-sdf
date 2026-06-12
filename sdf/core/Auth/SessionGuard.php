<?php

namespace SDF\Auth;

use SDF\Session;

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
 * Stores the authenticated user's ID in `session key '_auth_id'`
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

        $session = Session::getInstance();
        $id = $session->get('_auth_id');

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
     * @throws \RuntimeException If the user model has no id.
     */
    public function login(object $user): void
    {
        if (($user->id ?? null) === null) {
            throw new \RuntimeException('Cannot login user with null id.');
        }
        $this->userInstance = $user;
        $session = Session::getInstance();
        $session->set('_auth_id', $user->id);
        $session->regenerate(true);
    }

    /**
     * Log the current user out.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->userInstance = null;
        $session = Session::getInstance();
        $session->remove('_auth_id');
        $session->regenerate(true);
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
