<?php

/**
 * smskSoft SDF Cache Driver Interface
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Cache
 * @file        CacheDriver.php
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

/**
 * PSR-16 (SimpleCache) compliant interface with tagging support.
 */
interface CacheDriver
{
    /**
     * Retrieve a value by key.
     *
     * @param string $key     Cache key.
     * @param mixed  $default Fallback when key is missing or expired.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persist a value with optional TTL.
     *
     * @param string                $key   Cache key.
     * @param mixed                 $value Value to store.
     * @param null|int|DateInterval $ttl   Seconds or DateInterval. null = no expiration.
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * Remove a single key.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Wipe all cached entries.
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Retrieve multiple keys at once.
     *
     * @param iterable $keys    List of cache keys.
     * @param mixed    $default Fallback for missing keys.
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * Persist multiple values at once.
     *
     * @param iterable              $values Key-value pairs.
     * @param null|int|DateInterval $ttl    Seconds or DateInterval.
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * Remove multiple keys at once.
     *
     * @param iterable $keys List of cache keys.
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * Check if a key exists and is not expired.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Assign tags to the next set() call(s).
     * Tags enable bulk invalidation via forgetTags().
     *
     * @param array $tags List of tag strings.
     * @return static
     */
    public function tags(array $tags): static;

    /**
     * Alias for clear().
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Alias for delete().
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function forget(string $key): bool;
}
