<?php

/**
 * smskSoft SDF CSRF Middleware
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Middleware
 * @file        CsrfMiddleware.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @since       Version 2.2
 * @filesource
 */

namespace SDF\Middleware;

use Closure;
use SDF\HttpResponseException;
use SDF\Middleware;
use SDF\Request;
use SDF\Session;

/**
 * CSRF protection middleware.
 *
 * Validates a per-session token on state-changing requests (POST, PUT, PATCH, DELETE).
 * Token is provided via X-CSRF-TOKEN header or _token POST field.
 *
 * Register:
 *   Router::middleware(\SDF\Middleware\CsrfMiddleware::class);
 *
 * In views use helper functions:
 *   <?= csrf_field() ?>
 *   <meta name="csrf-token" content="<?= csrf_token() ?>">
 */
class CsrfMiddleware implements Middleware
{
    protected string $sessionKey = '_csrf_token';

    /**
     * @param string|null $sessionKey Optional custom session key.
     */
    public function __construct(?string $sessionKey = null)
    {
        if ($sessionKey !== null) {
            $this->sessionKey = $sessionKey;
        }
    }

    /**
     * Handle the request — validate CSRF token on write methods.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws HttpResponseException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->header('X-CSRF-TOKEN')
                  ?? $request->post('_token');

            if (!$token || !$this->validateToken($token)) {
                throw new HttpResponseException('CSRF token mismatch.', 419);
            }
        }

        return $next($request);
    }

    /**
     * Validate the submitted token against the session.
     *
     * @param string $token Submitted token.
     * @return bool
     */
    protected function validateToken(string $token): bool
    {
        $session = Session::getInstance();
        $stored = $session->get($this->sessionKey);

        if ($stored === null) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Generate a new CSRF token and store it in the session.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::getInstance()->set('_csrf_token', $token);
        return $token;
    }

    /**
     * Get the current CSRF token (generates one if missing).
     *
     * @return string
     */
    public static function token(): string
    {
        $session = Session::getInstance();
        $token = $session->get('_csrf_token');

        if ($token === null) {
            $token = self::generateToken();
        }

        return $token;
    }

    /**
     * Return an HTML hidden input field with the CSRF token.
     *
     * @return string
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }
}
