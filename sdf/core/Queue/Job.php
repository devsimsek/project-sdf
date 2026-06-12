<?php

/**
 * smskSoft SDF Queue - Base Job
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Queue
 * @file        Job.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/queue
 * @since       v1.0
 * @filesource
 */

namespace SDF\Queue;

use ReflectionClass;

/**
 * Abstract base class for all queue jobs.
 *
 * Extend this class to define a unit of work that can be pushed onto a queue.
 * Constructor parameters are serialized for storage and deserialized for execution.
 */
abstract class Job
{
    protected ?string $id = null;
    protected int $attempts = 0;

    /**
     * Job constructor receives the payload data for this job.
     *
     * @param mixed ...$params Payload parameters passed when dispatching the job.
     */
    public function __construct(mixed ...$params)
    {
    }

    /**
     * Execute the job logic.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Serialize all constructor parameters for storage.
     *
     * @return array Serialized payload data.
     */
    public function getPayload(): array
    {
        $ref = new ReflectionClass($this);
        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return [];
        }
        $params = $ctor->getParameters();
        $payload = [];
        foreach ($params as $param) {
            $name = $param->getName();
            if (property_exists($this, $name)) {
                $payload[$name] = $this->{$name};
            } elseif ($param->isDefaultValueAvailable()) {
                $payload[$name] = $param->getDefaultValue();
            }
        }
        return $payload;
    }

    /**
     * Reconstruct a job instance from a stored payload array.
     *
     * @param array $payload Serialized payload data.
     * @return static
     */
    public static function fromPayload(array $payload): self
    {
        return new static(...$payload);
    }

    /**
     * Set the unique identifier for this job.
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the unique identifier for this job.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the number of execution attempts for this job.
     *
     * @param int $attempts
     * @return void
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * Get the number of execution attempts for this job.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
