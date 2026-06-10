<?php

namespace SDF\Auth;

use Closure;
use SDF\HttpResponseException;
use SDF\Middleware;
use SDF\Request;

/**
 * Project SDF Auth Middleware
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  Auth
 * @file        AuthMiddleware.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/auth
 * @since       Version 2.0
 * @filesource
 */

/**
 * Pipeline middleware that rejects unauthenticated requests with 401.
 *
 * Register in your route config:
 * ```php
 * Router::middleware(\SDF\Auth\AuthMiddleware::class);
 * ```
 *
 * To use a non-default guard, subclass and override the constructor
 * or call `Auth::guard('jwt')` before the middleware runs.
 */
class AuthMiddleware implements Middleware
{
    /** @var string Guard name to use for authentication. */
    protected string $guard;

    /**
     * @param string $guard Guard name (default: 'session').
     */
    public function __construct(string $guard = 'session')
    {
        $this->guard = $guard;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws HttpResponseException When unauthenticated.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::guard($this->guard)->check()) {
            throw new HttpResponseException('Unauthenticated', 401);
        }

        return $next($request);
    }
}
