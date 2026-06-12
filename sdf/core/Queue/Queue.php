<?php

/**
 * smskSoft SDF Queue - Queue Interface
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Queue
 * @file        Queue.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/queue
 * @since       v1.0
 * @filesource
 */

namespace SDF\Queue;

/**
 * Contract for queue backends.
 *
 * Implementations provide push, pop, release, delete, size, and clear operations
 * for managing jobs in a specific storage backend (database, Redis, etc.).
 */
interface Queue
{
    /**
     * Push a job onto the queue and return its unique identifier.
     *
     * @param Job $job The job to enqueue.
     * @return string The assigned job ID.
     */
    public function push(Job $job): string;

    /**
     * Pop the next available job from the queue.
     *
     * @return Job|null A job instance, or null if the queue is empty.
     */
    public function pop(): ?Job;

    /**
     * Release a job back onto the queue with an optional delay.
     *
     * @param Job $job  The job to release.
     * @param int  $delay Delay in seconds before the job becomes available.
     * @return void
     */
    public function release(Job $job, int $delay = 0): void;

    /**
     * Delete a job from the queue.
     *
     * @param Job $job The job to delete.
     * @return void
     */
    public function delete(Job $job): void;

    /**
     * Get the number of pending jobs in the queue.
     *
     * @return int
     */
    public function size(): int;

    /**
     * Remove all jobs from the queue.
     *
     * @return void
     */
    public function clear(): void;
}
