<?php

namespace SDF\Auth;

/**
 * Project SDF Auth Guard Contract
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        Guard.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Contract for authentication guards.
 *
 * Guards define how users are authenticated and retrieved
 * during a request life-cycle (stateless JWT or session-based).
 */
interface Guard
{
    /**
     * Determine if the current request has an authenticated user.
     *
     * @return bool
     */
    public function check(): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return object|null
     */
    public function user(): ?object;

    /**
     * Log a user in.
     *
     * @param object $user User model instance.
     * @return void
     */
    public function login(object $user): void;

    /**
     * Log the current user out.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Attempt to authenticate with credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @return bool True on success.
     */
    public function attempt(array $credentials): bool;
}
