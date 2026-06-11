<?php

/**
 * smskSoft SDF Queue - Worker
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Queue
 * @file        Worker.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/queue
 * @since       v1.0
 * @filesource
 */

namespace SDF\Queue;

use Throwable;
use SDF\Logger;

/**
 * Worker that processes jobs from a queue.
 *
 * Provides both single-job (workNext) and infinite-loop (work, run) processing modes.
 * Exceptions during job handling are caught and logged via SDF\Logger.
 */
class Worker
{
    protected Queue $queue;

    /**
     * Constructor.
     *
     * @param Queue $queue The queue implementation to consume jobs from.
     */
    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Run an infinite loop, processing jobs as they become available.
     *
     * @return void
     */
    public function work(): void
    {
        while (true) {
            $this->workNext();
        }
    }

    /**
     * Pop and handle a single job from the queue.
     *
     * @return Job|null The processed job, or null if the queue was empty.
     */
    public function workNext(): ?Job
    {
        $job = $this->queue->pop();
        if ($job === null) {
            return null;
        }

        try {
            $job->handle();
            $this->queue->delete($job);
        } catch (Throwable $e) {
            Logger::error('Queue Worker: job failed', [
                'job' => get_class($job),
                'id' => $job->getId(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $job;
    }

    /**
     * Process up to a given number of jobs from the queue.
     *
     * @param int $maxJobs Maximum jobs to process. 0 means unlimited (infinite loop).
     * @return void
     */
    public function run(int $maxJobs = 0): void
    {
        $processed = 0;
        while ($maxJobs === 0 || $processed < $maxJobs) {
            $job = $this->workNext();
            if ($job === null) {
                if ($maxJobs > 0) {
                    break;
                }
                continue;
            }
            $processed++;
        }
    }
}
