<?php

/**
 * smskSoft SDF Queue - Redis Queue
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Queue
 * @file        RedisQueue.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/queue
 * @since       v1.0
 * @filesource
 */

namespace SDF\Queue;

use Redis;
use RedisException;

/**
 * Redis-backed queue implementation using phpredis.
 *
 * Uses RPUSH for pushing, BLPOP for blocking pop, and LLEN for size.
 * Jobs are stored as JSON-encoded payloads under a configurable key prefix.
 */
class RedisQueue implements Queue
{
    protected Redis $redis;
    protected string $prefix;
    public string $defaultQueue = 'default';

    /**
     * Constructor.
     *
     * @param Redis  $redis  An active phpredis instance.
     * @param string $prefix Key prefix for queue namespacing.
     * @param string|null $queue Default queue name.
     */
    public function __construct(Redis $redis, string $prefix = 'sdf:queue:', ?string $queue = null)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        if ($queue !== null) {
            $this->defaultQueue = $queue;
        }
    }

    /**
     * Build the Redis key for a given queue name.
     *
     * @param string $queue
     * @return string
     */
    protected function key(string $queue): string
    {
        return $this->prefix . $queue;
    }

    /**
     * Push a job onto the queue.
     *
     * @param Job $job
     * @return string
     * @throws RedisException
     */
    public function push(Job $job): string
    {
        $payload = json_encode([
            'class' => get_class($job),
            'data' => $job->getPayload(),
        ], JSON_UNESCAPED_SLASHES);

        $id = uniqid('', true);
        $job->setId($id);

        $wrapped = json_encode([
            'id' => $id,
            'payload' => $payload,
            'attempts' => $job->getAttempts(),
        ], JSON_UNESCAPED_SLASHES);

        $this->redis->rPush($this->key($this->defaultQueue), $wrapped);
        return $id;
    }

    /**
     * Pop the next available job from the queue.
     *
     * Uses BLPOP with a 5-second timeout to block until a job is available.
     *
     * @return Job|null
     * @throws RedisException
     */
    public function pop(): ?Job
    {
        $this->migrateDelayed();
        $result = $this->redis->blPop($this->key($this->defaultQueue), 5);

        if (!$result || !is_array($result) || count($result) < 2) {
            return null;
        }

        $data = json_decode($result[1], true);
        if (!is_array($data) || !isset($data['payload'])) {
            return null;
        }

        $decoded = json_decode($data['payload'], true);
        if (!is_array($decoded) || !isset($decoded['class'], $decoded['data'])) {
            return null;
        }

        $class = $decoded['class'];
        if (!class_exists($class)) {
            return null;
        }

        $job = $class::fromPayload($decoded['data']);
        $job->setId($data['id'] ?? uniqid('', true));
        $job->setAttempts((int) ($data['attempts'] ?? 0));
        return $job;
    }

    /**
     * Release a job back onto the queue with an optional delay.
     *
     * Uses a simple RPUSH; for true delayed delivery consider Redis keyspace
     * notifications or a sorted set approach.
     *
     * @param Job $job
     * @param int  $delay Delay in seconds (not supported by simple list).
     * @return void
     * @throws RedisException
     */
    public function release(Job $job, int $delay = 0): void
    {
        $payload = json_encode([
            'class' => get_class($job),
            'data' => $job->getPayload(),
        ], JSON_UNESCAPED_SLASHES);

        $wrapped = json_encode([
            'id' => $job->getId() ?? uniqid('', true),
            'payload' => $payload,
            'attempts' => $job->getAttempts() + 1,
        ], JSON_UNESCAPED_SLASHES);

        if ($delay > 0) {
            $this->redis->zAdd(
                $this->key($this->defaultQueue) . ':delayed',
                time() + $delay,
                $wrapped
            );
        } else {
            $this->redis->rPush($this->key($this->defaultQueue), $wrapped);
        }
    }

    /**
     * Delete a job from the queue.
     *
     * Since Redis lists do not support random deletion, this is a no-op.
     * Jobs are naturally removed when popped.
     *
     * @param Job $job
     * @return void
     */
    public function delete(Job $job): void
    {
    }

    /**
     * Get the number of pending jobs in the queue.
     *
     * @return int
     * @throws RedisException
     */
    public function size(): int
    {
        return $this->redis->lLen($this->key($this->defaultQueue));
    }

    /**
     * Remove all jobs from the queue.
     *
     * @return void
     * @throws RedisException
     */
    public function clear(): void
    {
        $this->redis->del($this->key($this->defaultQueue));
    }

    protected function migrateDelayed(): void
    {
        $delayedKey = $this->key($this->defaultQueue) . ':delayed';
        $now = time();
        $batch = $this->redis->zRangeByScore($delayedKey, '-inf', (string) $now);
        if (!empty($batch)) {
            foreach ($batch as $wrapped) {
                $this->redis->rPush($this->key($this->defaultQueue), $wrapped);
            }
            $this->redis->zRemRangeByScore($delayedKey, '-inf', (string) $now);
        }
    }
}
