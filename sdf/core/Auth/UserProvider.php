<?php

namespace SDF\Auth;

/**
 * Project SDF Auth User Provider
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        UserProvider.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Load users from storage for authentication guards.
 */
class UserProvider
{
    /** @var class-string FQCN of the user model. */
    private string $model;

    /**
     * @param class-string $model User model class (e.g. \App\Models\User::class).
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their primary key.
     *
     * @param mixed $id Primary key value.
     * @return object|null
     */
    public function retrieveById(mixed $id): ?object
    {
        $model = $this->model;
        return $model::find($id);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * Iterates over the credentials array, skipping non-credential
     * fields (e.g. 'password'), and returns the first match.
     * Typically used with ['email' => $email] or ['username' => $name].
     *
     * @param array $credentials Key-value pairs to search by.
     * @return object|null
     */
    public function retrieveByCredentials(array $credentials): ?object
    {
        $model = $this->model;
        $allowed = ['email', 'username', 'id'];

        foreach ($credentials as $key => $value) {
            if (!in_array($key, ['password', 'password_confirmation'], true) && in_array($key, $allowed, true)) {
                $user = $model::where($key, $value)->first();
                if ($user !== null) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Validate the given credentials against a user instance.
     *
     * Expects the user model to expose a `password` property
     * containing the bcrypt hash.
     *
     * @param object $user        User model instance.
     * @param array  $credentials ['password' => '...'] to verify.
     * @return bool
     */
    public function validateCredentials(object $user, array $credentials): bool
    {
        return password_verify(
            $credentials['password'] ?? '',
            $user->password ?? '',
        );
    }

    /**
     * Rehash the password if the current bcrypt cost is outdated.
     *
     * @param object $user    User model instance with a `password` property.
     * @param string $password Plain-text password to rehash.
     * @return void
     */
    public function rehashPasswordIfNeeded(object $user, string $password): void
    {
        if (password_needs_rehash($user->password ?? '', PASSWORD_BCRYPT)) {
            $user->password = password_hash($password, PASSWORD_BCRYPT);
            $user->save();
        }
    }
}
