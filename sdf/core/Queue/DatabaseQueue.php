<?php

/**
 * smskSoft SDF Queue - Database Queue
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Queue
 * @file        DatabaseQueue.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/queue
 * @since       v1.0
 * @filesource
 */

namespace SDF\Queue;

use PDO;
use SDF\Spark;

/**
 * Database-backed queue implementation using SQLite/MySQL/PostgreSQL via Spark PDO.
 *
 * Stores jobs in a `jobs` table and uses transactional pop to safely claim a job.
 */
class DatabaseQueue implements Queue
{
    protected PDO $pdo;
    protected string $table = 'jobs';
    public string $defaultQueue = 'default';

    /**
     * Constructor.
     *
     * Automatically creates the jobs table if it does not exist.
     *
     * @param string|null $queue Default queue name.
     */
    public function __construct(?string $queue = null)
    {
        $this->pdo = Spark::pdo();
        if ($queue !== null) {
            $this->defaultQueue = $queue;
        }
        $this->createTable();
    }

    /**
     * Create the jobs table if it does not exist.
     *
     * @return void
     */
    protected function createTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue TEXT NOT NULL,
                payload TEXT NOT NULL,
                attempts INTEGER DEFAULT 0,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )";
        } elseif ($driver === 'pgsql' || $driver === 'postgres') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id SERIAL PRIMARY KEY,
                queue TEXT NOT NULL,
                payload TEXT NOT NULL,
                attempts INTEGER DEFAULT 0,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                attempts INT DEFAULT 0,
                available_at INT NOT NULL,
                created_at INT NOT NULL
            )";
        }
        $this->pdo->exec($sql);
    }

    /**
     * Push a job onto the queue.
     *
     * @param Job $job
     * @return string
     */
    public function push(Job $job): string
    {
        $queue = $this->defaultQueue;
        $payload = json_encode([
            'class' => get_class($job),
            'data' => $job->getPayload(),
        ], JSON_UNESCAPED_SLASHES);
        $now = time();
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (queue, payload, attempts, available_at, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$queue, $payload, 0, $now, $now]);
        $id = $this->pdo->lastInsertId();
        $job->setId((string) $id);
        return (string) $id;
    }

    /**
     * Pop the next available job from the queue.
     *
     * Uses a transaction to safely select and delete the oldest available job.
     *
     * @return Job|null
     */
    public function pop(): ?Job
    {
        $this->pdo->beginTransaction();
        $now = time();

        $stmt = $this->pdo->prepare(
            "SELECT id, payload, attempts FROM {$this->table}
             WHERE queue = ? AND available_at <= ?
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute([$this->defaultQueue, $now]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->pdo->commit();
            return null;
        }

        $decoded = json_decode($row['payload'], true);
        if (!is_array($decoded) || !isset($decoded['class'], $decoded['data'])) {
            $this->pdo->commit();
            return null;
        }

        $class = $decoded['class'];
        if (!class_exists($class)) {
            $this->pdo->commit();
            return null;
        }

        $job = $class::fromPayload($decoded['data']);
        $job->setId((string) $row['id']);
        $job->setAttempts((int) $row['attempts']);

        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = ?"
        );
        $deleteStmt->execute([$row['id']]);

        $this->pdo->commit();
        return $job;
    }

    /**
     * Release a job back onto the queue with an optional delay.
     *
     * @param Job $job
     * @param int  $delay Delay in seconds.
     * @return void
     */
    public function release(Job $job, int $delay = 0): void
    {
        $queue = $this->defaultQueue;
        $payload = json_encode([
            'class' => get_class($job),
            'data' => $job->getPayload(),
        ], JSON_UNESCAPED_SLASHES);
        $now = time();
        $availableAt = $now + $delay;
        $attempts = $job->getAttempts() + 1;

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (queue, payload, attempts, available_at, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$queue, $payload, $attempts, $availableAt, $now]);
    }

    /**
     * Delete a job from the queue.
     *
     * @param Job $job
     * @return void
     */
    public function delete(Job $job): void
    {
        $id = $job->getId();
        if ($id === null) {
            return;
        }
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /**
     * Get the number of pending jobs in the default queue.
     *
     * @return int
     */
    public function size(): int
    {
        $now = time();
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE queue = ? AND available_at <= ?"
        );
        $stmt->execute([$this->defaultQueue, $now]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Remove all jobs from the queue.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->pdo->exec("DELETE FROM {$this->table}");
    }
}
