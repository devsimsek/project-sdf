<?php

/**
 * smskSoft SDF CORS Middleware
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Middleware
 * @file        CorsMiddleware.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @since       Version 2.2
 * @filesource
 */

namespace SDF\Middleware;

use Closure;
use SDF\Core;
use SDF\Middleware;
use SDF\Request;

/**
 * CORS (Cross-Origin Resource Sharing) middleware.
 *
 * Handles preflight OPTIONS requests and sets CORS headers on responses.
 * Configured via app/config/cors.php.
 *
 * Register:
 *   Router::middleware(\SDF\Middleware\CorsMiddleware::class);
 */
class CorsMiddleware implements Middleware
{
    private ?array $config = null;

    /**
     * Load CORS config from app/config/cors.php.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = Core::coreGetConfig('cors') ?: [
                'allowed_origins' => ['*'],
                'allowed_origins_patterns' => [],
                'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
                'exposed_headers' => [],
                'max_age' => 86400,
                'allow_credentials' => false,
            ];
        }
        return $this->config;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $config = $this->getConfig();
        $origin = $request->header('Origin') ?? '*';

        if (!$this->isOriginAllowed($origin, $config)) {
            return $next($request);
        }

        if ($request->isOptions()) {
            $this->setPreflightHeaders($origin, $config);
            return '';
        }

        $response = $next($request);

        $this->setCorsHeaders($origin, $config);

        return $response;
    }

    /**
     * Check whether the given origin is allowed.
     *
     * @param string $origin
     * @param array  $config
     * @return bool
     */
    protected function isOriginAllowed(string $origin, array $config): bool
    {
        $allowed = $config['allowed_origins'] ?? [];
        $allowCredentials = !empty($config['allow_credentials']);

        if (in_array('*', $allowed, true)) {
            return !$allowCredentials;
        }

        if (in_array($origin, $allowed, true)) {
            return true;
        }

        foreach ($config['allowed_origins_patterns'] ?? [] as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set CORS headers on the response.
     *
     * @param string $origin
     * @param array  $config
     * @return void
     */
    protected function setCorsHeaders(string $origin, array $config): void
    {
        $allowAll = in_array('*', $config['allowed_origins'] ?? [], true);
        $allowCredentials = !empty($config['allow_credentials']);

        if ($allowAll && $allowCredentials) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: ' . ($allowAll ? '*' : $origin));
        }

        if (!$allowAll || $allowCredentials) {
            header('Vary: Origin');
        }

        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        if (!empty($config['exposed_headers'])) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $config['exposed_headers']));
        }
    }

    /**
     * Set preflight OPTIONS response headers.
     *
     * @param string $origin
     * @param array  $config
     * @return void
     */
    protected function setPreflightHeaders(string $origin, array $config): void
    {
        $allowAll = in_array('*', $config['allowed_origins'] ?? [], true);
        $allowCredentials = !empty($config['allow_credentials']);

        if ($allowAll && $allowCredentials) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: ' . ($allowAll ? '*' : $origin));
        }

        if (!$allowAll || $allowCredentials) {
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods'] ?? []));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers'] ?? []));
        header('Access-Control-Max-Age: ' . ($config['max_age'] ?? 86400));

        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        header('HTTP/1.1 204 No Content');
    }
}
