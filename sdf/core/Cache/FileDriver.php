<?php

/**
 * smskSoft SDF File Cache Driver
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Cache
 * @file        FileDriver.php
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
 * File-based cache driver.
 * Stores serialized entries under a configurable directory.
 * No PHP extensions required. Supports TTL and tagging.
 */
class FileDriver implements CacheDriver
{
    /** @var string Storage directory path. */
    private string $path;

    /** @var array Active tags for the next set() call. */
    private array $activeTags = [];

    /** @var string File prefix for cache entries. */
    private string $prefix = 'sdf_cache_';

    /**
     * @param array $config 'path' and 'prefix' keys.
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? (sys_get_temp_dir() . '/sdf_cache/');
        $this->prefix = $config['prefix'] ?? 'sdf_cache_';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Retrieve a value by key.
     * Expired entries are deleted and return $default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->read($key);
        if ($data === null) {
            return $default;
        }
        if ($data['ttl'] > 0 && time() >= $data['expires']) {
            $this->delete($key);
            return $default;
        }
        return $data['value'];
    }

    /**
     * Persist a value with optional TTL and active tags.
     *
     * @param string                $key
     * @param mixed                 $value
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttlSeconds = Cache::ttlToSeconds($ttl);
        $expires = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $entry = [
            'value' => $value,
            'ttl' => $ttlSeconds,
            'expires' => $expires,
            'created' => time(),
            'tags' => $this->activeTags,
        ];

        $ok = $this->write($key, $entry);
        if ($ok && !empty($this->activeTags)) {
            $this->updateTagIndex($key);
            $this->activeTags = [];
        }
        return $ok;
    }

    /**
     * Remove a single key and its tag references.
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $file = $this->path($key);
        if (file_exists($file)) {
            $this->removeTagIndex($key);
            return unlink($file);
        }
        return true;
    }

    /**
     * Remove all cache files including tag index.
     *
     * @return bool
     */
    public function clear(): bool
    {
        $files = glob($this->path . $this->prefix . '*.cache');
        if ($files === false) {
            return false;
        }
        foreach ($files as $file) {
            unlink($file);
        }
        $tagFile = $this->path . $this->prefix . 'tag_index.cache';
        if (file_exists($tagFile)) {
            unlink($tagFile);
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
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
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
        return $this->get($key, $this) !== $this;
    }

    /**
     * Assign tags to the next set() call(s).
     *
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
        $index = $this->loadTagIndex();
        foreach ($tags as $tag) {
            $keys = $index[$tag] ?? [];
            foreach ($keys as $key) {
                $this->delete($key);
            }
            unset($index[$tag]);
        }
        $this->saveTagIndex($index);
    }

    /**
     * Build the file path for a given key.
     *
     * @param string $key
     * @return string
     */
    private function path(string $key): string
    {
        return $this->path . $this->prefix . md5($key) . '.cache';
    }

    /**
     * Read and deserialise a cache entry.
     *
     * @param string $key
     * @return array|null
     */
    private function read(string $key): ?array
    {
        $file = $this->path($key);
        if (!file_exists($file)) {
            return null;
        }
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        $data = unserialize($content);
        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    /**
     * Serialise and write a cache entry to a temp file with atomic rename.
     *
     * @param string $key
     * @param array  $data
     * @return bool
     */
    private function write(string $key, array $data): bool
    {
        $file = $this->path($key);
        $tmp = $file . '.tmp.' . getmypid();
        $written = file_put_contents($tmp, serialize($data));
        if ($written === false) {
            return false;
        }
        if (PHP_SAPI !== 'cli-server' && PHP_SAPI !== 'cli') {
            chmod($tmp, 0600);
        }
        $renamed = rename($tmp, $file);
        if (!$renamed) {
            unlink($tmp);
            return false;
        }
        return true;
    }

    /**
     * Register a key under its active tags.
     *
     * @param string $key
     * @return void
     */
    private function updateTagIndex(string $key): void
    {
        $index = $this->loadTagIndex();
        foreach ($this->activeTags as $tag) {
            $index[$tag][] = $key;
            $index[$tag] = array_unique($index[$tag]);
        }
        $this->saveTagIndex($index);
    }

    /**
     * Unlink a key from the tag index.
     *
     * @param string $key
     * @return void
     */
    private function removeTagIndex(string $key): void
    {
        $index = $this->loadTagIndex();
        foreach ($index as $tag => $keys) {
            $index[$tag] = array_values(array_filter($keys, fn ($k) => $k !== $key));
            if (empty($index[$tag])) {
                unset($index[$tag]);
            }
        }
        $this->saveTagIndex($index);
    }

    /**
     * @return array
     */
    private function loadTagIndex(): array
    {
        $file = $this->path . $this->prefix . 'tag_index.cache';
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }
        $data = unserialize($content);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array $index
     * @return void
     */
    private function saveTagIndex(array $index): void
    {
        $file = $this->path . $this->prefix . 'tag_index.cache';
        file_put_contents($file, serialize($index));
    }
}
