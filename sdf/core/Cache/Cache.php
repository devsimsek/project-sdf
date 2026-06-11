<?php

/**
 * smskSoft SDF Cache Facade
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Cache
 * @file        Cache.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/caching
 * @since       Version 2.2
 * @filesource
 */

namespace SDF\Cache;

use DateInterval;
use DateTime;
use SDF\Core;

/**
 * Cache facade - PSR-16 (SimpleCache) compliant static proxy.
 *
 * Usage:
 *   Cache::set('key', $value, 3600);
 *   $val = Cache::get('key', 'default');
 *   Cache::tags(['people'])->set('user_1', 'Alice');
 *
 * The underlying driver is resolved from app/config/cache.php.
 */
class Cache
{
    /** @var CacheDriver|null Resolved driver singleton. */
    private static ?CacheDriver $instance = null;

    /**
     * Retrieve a value by key.
     *
     * @param string $key     Cache key.
     * @param mixed  $default Fallback when key is missing or expired.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    /**
     * Persist a value with optional TTL.
     *
     * @param string                $key   Cache key.
     * @param mixed                 $value Value to store.
     * @param null|int|DateInterval $ttl   Seconds or DateInterval. null = no expiration.
     * @return bool
     */
    public static function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return self::driver()->set($key, $value, $ttl);
    }

    /**
     * Remove a single key.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public static function delete(string $key): bool
    {
        return self::driver()->delete($key);
    }

    /**
     * Wipe all cached entries.
     *
     * @return bool
     */
    public static function clear(): bool
    {
        return self::driver()->clear();
    }

    /**
     * Retrieve multiple keys at once.
     *
     * @param iterable $keys    List of cache keys.
     * @param mixed    $default Fallback for missing keys.
     * @return iterable<string, mixed>
     */
    public static function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return self::driver()->getMultiple($keys, $default);
    }

    /**
     * Persist multiple values at once.
     *
     * @param iterable              $values Key-value pairs.
     * @param null|int|DateInterval $ttl    Seconds or DateInterval.
     * @return bool
     */
    public static function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return self::driver()->setMultiple($values, $ttl);
    }

    /**
     * Remove multiple keys at once.
     *
     * @param iterable $keys List of cache keys.
     * @return bool
     */
    public static function deleteMultiple(iterable $keys): bool
    {
        return self::driver()->deleteMultiple($keys);
    }

    /**
     * Check if a key exists and is not expired.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    /**
     * Assign tags to the next set() call(s).
     *
     * @param array $tags List of tag strings.
     * @return CacheDriver
     */
    public static function tags(array $tags): CacheDriver
    {
        return self::driver()->tags($tags);
    }

    /**
     * Alias for delete().
     *
     * @param string $key Cache key.
     * @return bool
     */
    public static function forget(string $key): bool
    {
        return self::driver()->delete($key);
    }

    /**
     * Alias for clear().
     *
     * @return bool
     */
    public static function flush(): bool
    {
        return self::driver()->flush();
    }

    /**
     * Get the underlying driver instance.
     *
     * @return CacheDriver
     */
    public static function driver(): CacheDriver
    {
        if (self::$instance === null) {
            self::$instance = self::resolveDriver();
        }
        return self::$instance;
    }

    /**
     * Override the driver at runtime (useful in tests).
     *
     * @param CacheDriver $driver
     * @return void
     */
    public static function setDriver(CacheDriver $driver): void
    {
        self::$instance = $driver;
    }

    /**
     * Reset the resolved driver (used in test teardown).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Resolve a driver from app/config/cache.php.
     *
     * @return CacheDriver
     */
    private static function resolveDriver(): CacheDriver
    {
        $config = Core::coreGetConfig('cache') ?: [];
        $driver = $config['driver'] ?? 'file';

        $driverMap = [
            'redis' => 'RedisDriver',
            'memcached' => 'MemcachedDriver',
            'file' => 'FileDriver',
        ];
        $class = $driverMap[$driver] ?? 'FileDriver';
        $file = __DIR__ . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
        }

        $fqcn = __NAMESPACE__ . '\\' . $class;
        $configKey = ($class === 'FileDriver') ? 'file' : $driver;
        return new $fqcn($config[$configKey] ?? []);
    }

    /**
     * Convert a mixed TTL value to absolute seconds.
     *
     * @param null|int|DateInterval $ttl
     * @return int 0 means no expiration.
     */
    public static function ttlToSeconds(null|int|DateInterval $ttl): int
    {
        if ($ttl === null) {
            return 0;
        }
        if ($ttl instanceof DateInterval) {
            $ref = new DateTime('@0');
            $end = clone $ref;
            $end->add($ttl);
            return (int) $end->format('U') - (int) $ref->format('U');
        }
        return $ttl;
    }

    /**
     * Sanitise a cache key to prevent filesystem / protocol issues.
     *
     * @param string $key Raw key.
     * @return string Sanitised key.
     */
    public static function normalizeKey(string $key): string
    {
        $key = preg_replace('/[{}()\/\\\@:"]/', '_', $key);
        return trim(preg_replace('/[^a-zA-Z0-9_.!-]/', '', $key));
    }
}
