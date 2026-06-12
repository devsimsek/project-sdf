<?php

/**
 * smskSoft SDF Redis Cache Driver
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Cache
 * @file        RedisDriver.php
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
use Exception;

/**
 * Redis-backed cache driver.
 * Requires the phpredis extension. Gracefully degrades when unavailable.
 * Supports TTL and tagging via Redis sets.
 */
class RedisDriver implements CacheDriver
{
    /** @var \Redis|null Redis connection instance (null when unavailable). */
    private ?object $redis = null;

    /** @var bool Whether the Redis server is reachable. */
    private bool $available = false;

    /** @var array Active tags for the next set() call. */
    private array $activeTags = [];

    /** @var string Key prefix for all Redis entries. */
    private string $prefix;

    /** @var array Raw config array passed to constructor. */
    private array $config;

    /**
     * @param array $config Host, port, password, database, timeout, prefix.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'sdf_cache:';
        $this->connect();
    }

    /**
     * Attempt a connection. Sets $available based on result.
     *
     * @return void
     */
    private function connect(): void
    {
        if (!extension_loaded('redis')) {
            return;
        }
        try {
            $this->redis = new \Redis();
            $host = $this->config['host'] ?? '127.0.0.1';
            $port = $this->config['port'] ?? 6379;
            $timeout = $this->config['timeout'] ?? 2.5;
            $this->redis->connect($host, $port, $timeout);
            if (!empty($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }
            if (!empty($this->config['database'])) {
                $this->redis->select($this->config['database']);
            }
            $this->available = true;
        } catch (Exception) {
            $this->available = false;
        }
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->available) {
            return $default;
        }
        $value = $this->redis->get($this->prefix . $key);
        if ($value === false) {
            return $default;
        }
        $data = unserialize($value, ['allowed_classes' => false]);
        return $data !== false ? $data : $default;
    }

    /**
     * @param string                $key
     * @param mixed                 $value
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if (!$this->available) {
            return false;
        }
        $ttlSeconds = Cache::ttlToSeconds($ttl);
        $serialized = serialize($value);
        $k = $this->prefix . $key;

        if ($ttlSeconds > 0) {
            $ok = $this->redis->setex($k, $ttlSeconds, $serialized);
        } else {
            $ok = $this->redis->set($k, $serialized);
        }

        if ($ok && !empty($this->activeTags)) {
            foreach ($this->activeTags as $tag) {
                $this->redis->sAdd($this->prefix . 'tag:' . $tag, $key);
            }
            $this->activeTags = [];
        }
        return $ok;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        if (!$this->available) {
            return false;
        }
        $this->removeTagIndex($key);
        return $this->redis->del($this->prefix . $key) > 0;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        if (!$this->available) {
            return false;
        }
        $iterator = null;
        $keys = [];
        while ($ret = $this->redis->scan($iterator, $this->prefix . '*')) {
            foreach ($ret as $key) {
                $keys[] = $key;
            }
        }
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @param mixed    $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if (!$this->available) {
            $result = [];
            foreach ($keys as $k) {
                $result[$k] = $default;
            }
            return $result;
        }
        $prefixed = [];
        $map = [];
        foreach ($keys as $k) {
            $prefixed[] = $this->prefix . $k;
            $map[$this->prefix . $k] = $k;
        }
        $values = $this->redis->mGet($prefixed);
        $result = [];
        foreach ($values as $i => $v) {
            $originalKey = $map[$prefixed[$i]];
            $data = is_string($v) ? unserialize($v, ['allowed_classes' => false]) : false;
            $result[$originalKey] = $data !== false ? $data : $default;
        }
        return $result;
    }

    /**
     * @param iterable              $values
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        if (!$this->available) {
            return false;
        }
        $ok = true;
        $this->redis->multi();
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $ok = false;
            }
        }
        $this->redis->exec();
        return $ok;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $ok = false;
            }
        }
        return $ok;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->available) {
            return false;
        }
        return $this->redis->exists($this->prefix . $key);
    }

    /**
     * @param array $tags
     * @return static
     */
    public function tags(array $tags): static
    {
        $this->activeTags = $tags;
        return $this;
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        return $this->clear();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * Invalidate all entries matching given tags.
     *
     * @param array $tags
     * @return void
     */
    public function forgetTags(array $tags): void
    {
        if (!$this->available) {
            return;
        }
        foreach ($tags as $tag) {
            $keys = $this->redis->sMembers($this->prefix . 'tag:' . $tag);
            if (!empty($keys)) {
                $prefixed = [];
                foreach ($keys as $k) {
                    $prefixed[] = $this->prefix . $k;
                }
                $this->redis->del($prefixed);
                $this->redis->del($this->prefix . 'tag:' . $tag);
            }
        }
    }

    /**
     * Remove key from all tag sets.
     *
     * @param string $key
     * @return void
     */
    private function removeTagIndex(string $key): void
    {
        if (!$this->available) {
            return;
        }
        $tagKeys = $this->redis->keys($this->prefix . 'tag:*');
        foreach ($tagKeys as $tk) {
            $this->redis->sRem($tk, $key);
        }
    }

    /**
     * Check whether the Redis connection is live.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }
}
