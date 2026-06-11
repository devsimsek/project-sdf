<?php

/**
 * smskSoft SDF Rate-Limit Middleware
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Middleware
 * @file        RateLimitMiddleware.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @since       Version 2.2
 * @filesource
 */

namespace SDF\Middleware;

use Closure;
use SDF\Cache\Cache;
use SDF\HttpResponseException;
use SDF\Middleware;
use SDF\Request;

/**
 * Per-IP / per-route rate-limiting middleware.
 *
 * Uses the Cache facade for storage.
 *
 * Register:
 *   Router::middleware(\SDF\Middleware\RateLimitMiddleware::class);
 *
 * Custom limits per route:
 *   Router::middleware(new \SDF\Middleware\RateLimitMiddleware(
 *       maxAttempts: 30, decaySeconds: 60
 *   ));
 */
class RateLimitMiddleware implements Middleware
{
    protected int $maxAttempts;
    protected int $decaySeconds;
    protected string $prefix = 'ratelimit:';

    /**
     * @param int    $maxAttempts  Max hits per window (default: 60).
     * @param int    $decaySeconds Window length in seconds (default: 60).
     * @param string $prefix       Cache key prefix.
     */
    public function __construct(
        int $maxAttempts = 60,
        int $decaySeconds = 60,
        string $prefix = 'ratelimit:',
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->prefix = $prefix;
    }

    /**
     * Handle the request — enforce rate limit.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws HttpResponseException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $key = $this->resolveKey($request);
        $attempts = $this->getAttempts($key);

        if ($attempts >= $this->maxAttempts) {
            $retryAfter = $this->retryAfter($key);
            header('Retry-After: ' . $retryAfter);
            header('X-RateLimit-Limit: ' . $this->maxAttempts);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . (time() + $retryAfter));
            throw new HttpResponseException('Too Many Requests.', 429);
        }

        $this->hit($key);

        $remaining = max(0, $this->maxAttempts - ($attempts + 1));
        header('X-RateLimit-Limit: ' . $this->maxAttempts);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . (time() + $this->decaySeconds));

        return $next($request);
    }

    /**
     * Build the cache key (per-IP + per-route).
     *
     * @param Request $request
     * @return string
     */
    protected function resolveKey(Request $request): string
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $this->prefix . sha1("{$ip}|{$path}");
    }

    /**
     * Get current attempt count for the key.
     *
     * @param string $key
     * @return int
     */
    protected function getAttempts(string $key): int
    {
        $data = Cache::get($key);
        if ($data === null) {
            return 0;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded) || !isset($decoded['attempts'], $decoded['expires_at'])) {
            Cache::delete($key);
            return 0;
        }

        if (time() > $decoded['expires_at']) {
            Cache::delete($key);
            return 0;
        }

        return (int) $decoded['attempts'];
    }

    /**
     * Record a hit for the key.
     *
     * @param string $key
     * @return void
     */
    protected function hit(string $key): void
    {
        $data = Cache::get($key);
        $now = time();

        if ($data === null) {
            $payload = json_encode([
                'attempts' => 1,
                'expires_at' => $now + $this->decaySeconds,
            ]);
            Cache::set($key, $payload, $this->decaySeconds);
            return;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded) || !isset($decoded['attempts'])) {
            $payload = json_encode([
                'attempts' => 1,
                'expires_at' => $now + $this->decaySeconds,
            ]);
            Cache::set($key, $payload, $this->decaySeconds);
            return;
        }

        if (time() > ($decoded['expires_at'] ?? 0)) {
            $decoded['attempts'] = 0;
            $decoded['expires_at'] = $now + $this->decaySeconds;
        }

        $decoded['attempts']++;
        Cache::set($key, json_encode($decoded), $this->decaySeconds);
    }

    /**
     * Seconds until the client can retry.
     *
     * @param string $key
     * @return int
     */
    protected function retryAfter(string $key): int
    {
        $data = Cache::get($key);
        if ($data === null) {
            return $this->decaySeconds;
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded) || !isset($decoded['expires_at'])) {
            return $this->decaySeconds;
        }

        return max(0, $decoded['expires_at'] - time());
    }
}
