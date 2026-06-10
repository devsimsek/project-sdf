<?php

/**
 * smskSoft SDF Memcached Cache Driver
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Cache
 * @file        MemcachedDriver.php
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
 * Memcached-backed cache driver.
 * Requires the memcached PHP extension. Gracefully degrades when unavailable.
 * Supports TTL and tagging via an in-memory tag index.
 */
class MemcachedDriver implements CacheDriver
{
    /** @var \Memcached|null Memcached connection instance (null when unavailable). */
    private ?\Memcached $memcached = null;

    /** @var bool Whether the Memcached server is reachable. */
    private bool $available = false;

    /** @var array Active tags for the next set() call. */
    private array $activeTags = [];

    /** @var string Key prefix for all Memcached entries. */
    private string $prefix;

    /** @var array Raw config array passed to constructor. */
    private array $config;

    /**
     * @param array $config Host, port, prefix.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'sdf_cache_';
        $this->connect();
    }

    /**
     * Attempt a connection. Sets $available based on getStats() result.
     *
     * @return void
     */
    private function connect(): void
    {
        if (!extension_loaded('memcached')) {
            return;
        }
        try {
            $this->memcached = new \Memcached();
            $host = $this->config['host'] ?? '127.0.0.1';
            $port = $this->config['port'] ?? 11211;
            $this->memcached->addServer($host, $port);
            $this->memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 2000);
            $stats = @$this->memcached->getStats();
            if ($stats === false || !isset($stats[$host . ':' . $port])) {
                $this->available = false;
                return;
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
        $value = $this->memcached->get($this->prefix . $key);
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return $default;
        }
        return $value;
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
        $expiration = $ttlSeconds > 0 ? $ttlSeconds : 0;
        $k = $this->prefix . $key;

        $ok = $this->memcached->set($k, $value, $expiration);

        if ($ok && !empty($this->activeTags)) {
            $tagIndex = $this->loadTagIndex();
            foreach ($this->activeTags as $tag) {
                $tagIndex[$tag][] = $key;
                $tagIndex[$tag] = array_unique($tagIndex[$tag]);
            }
            $this->saveTagIndex($tagIndex);
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
        return $this->memcached->delete($this->prefix . $key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        if (!$this->available) {
            return false;
        }
        $this->memcached->delete($this->prefix . 'tag_index');
        return $this->memcached->flush();
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
        $values = $this->memcached->getMulti($prefixed);
        $result = [];
        foreach ($map as $prefixedKey => $originalKey) {
            $result[$originalKey] = isset($values[$prefixedKey]) ? $values[$prefixedKey] : $default;
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
        $ok = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $ok = false;
            }
        }
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
        $this->memcached->get($this->prefix . $key);
        return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
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
        $tagIndex = $this->loadTagIndex();
        foreach ($tags as $tag) {
            $keys = $tagIndex[$tag] ?? [];
            foreach ($keys as $key) {
                $this->memcached->delete($this->prefix . $key);
            }
            unset($tagIndex[$tag]);
        }
        $this->saveTagIndex($tagIndex);
    }

    /**
     * @return array
     */
    private function loadTagIndex(): array
    {
        $data = $this->memcached->get($this->prefix . 'tag_index');
        return is_array($data) ? $data : [];
    }

    /**
     * @param array $index
     * @return void
     */
    private function saveTagIndex(array $index): void
    {
        $this->memcached->set($this->prefix . 'tag_index', $index, 0);
    }

    /**
     * @param string $key
     * @return void
     */
    private function removeTagIndex(string $key): void
    {
        $tagIndex = $this->loadTagIndex();
        foreach ($tagIndex as $tag => $keys) {
            $tagIndex[$tag] = array_values(array_filter($keys, fn($k) => $k !== $key));
            if (empty($tagIndex[$tag])) {
                unset($tagIndex[$tag]);
            }
        }
        $this->saveTagIndex($tagIndex);
    }

    /**
     * Check whether the Memcached connection is live.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }
}
