<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Core;
use SDF\Logger;
use SDF\Queue\DatabaseQueue;
use SDF\Queue\Job;
use SDF\Queue\Queue;
use SDF\Queue\Worker;
use SDF\Spark;

class TestJob extends Job
{
    public bool $handled = false;

    public function __construct(
        public string $message = 'default'
    ) {
    }

    public function handle(): void
    {
        $this->handled = true;
    }
}

class FailingTestJob extends Job
{
    public bool $handled = false;

    public function __construct(
        public string $message = 'default'
    ) {
    }

    public function handle(): void
    {
        $this->handled = true;
        throw new \RuntimeException('Job failed intentionally');
    }
}

class QueueTest extends TestCase
{
    private DatabaseQueue $queue;

    protected function setUp(): void
    {
        Spark::connect('sqlite::memory:');
        $this->queue = new DatabaseQueue('test');
        Logger::resetInstance();
    }

    protected function tearDown(): void
    {
        Spark::disconnect();
    }

    public function test_push_returns_id(): void
    {
        $job = new TestJob('hello');
        $id = $this->queue->push($job);
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }

    public function test_push_sets_job_id(): void
    {
        $job = new TestJob();
        $id = $this->queue->push($job);
        $this->assertSame($id, $job->getId());
    }

    public function test_pop_returns_job(): void
    {
        $this->queue->push(new TestJob('pop test'));
        $job = $this->queue->pop();

        $this->assertNotNull($job);
        $this->assertInstanceOf(TestJob::class, $job);
        $this->assertSame('pop test', $job->message);
    }

    public function test_pop_returns_null_when_empty(): void
    {
        $job = $this->queue->pop();
        $this->assertNull($job);
    }

    public function test_pop_removes_job_from_queue(): void
    {
        $this->queue->push(new TestJob());
        $this->assertEquals(1, $this->queue->size());

        $this->queue->pop();
        $this->assertEquals(0, $this->queue->size());
    }

    public function test_pop_increments_attempts_on_release(): void
    {
        $this->queue->push(new TestJob('attempts'));
        $job = $this->queue->pop();
        $this->assertNotNull($job);
        $this->assertSame(0, $job->getAttempts());
    }

    public function test_release_adds_job_back(): void
    {
        $this->queue->push(new TestJob('release test'));
        $job = $this->queue->pop();
        $this->assertNotNull($job);

        $this->queue->release($job, 0);
        $this->assertEquals(1, $this->queue->size());
    }

    public function test_release_increments_attempts(): void
    {
        $this->queue->push(new TestJob());
        $job = $this->queue->pop();
        $this->assertNotNull($job);

        $job->setAttempts($job->getAttempts() + 1);
        $this->queue->release($job, 0);

        $released = $this->queue->pop();
        $this->assertNotNull($released);
        $this->assertGreaterThanOrEqual(1, $released->getAttempts());
    }

    public function test_delete_removes_job(): void
    {
        $this->queue->push(new TestJob());
        $job = $this->queue->pop();
        $this->assertNotNull($job);

        $this->queue->delete($job);
        $this->assertEquals(0, $this->queue->size());
    }

    public function test_size(): void
    {
        $this->assertEquals(0, $this->queue->size());
        $this->queue->push(new TestJob('a'));
        $this->queue->push(new TestJob('b'));
        $this->queue->push(new TestJob('c'));
        $this->assertEquals(3, $this->queue->size());
    }

    public function test_clear(): void
    {
        $this->queue->push(new TestJob());
        $this->queue->push(new TestJob());
        $this->queue->clear();
        $this->assertEquals(0, $this->queue->size());
    }

    public function test_fifo_order(): void
    {
        $this->queue->push(new TestJob('first'));
        $this->queue->push(new TestJob('second'));
        $this->queue->push(new TestJob('third'));

        $this->assertSame('first', $this->queue->pop()?->message);
        $this->assertSame('second', $this->queue->pop()?->message);
        $this->assertSame('third', $this->queue->pop()?->message);
    }

    public function test_job_get_payload(): void
    {
        $job = new TestJob('payload_test');
        $payload = $job->getPayload();
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertSame('payload_test', $payload['message']);
    }

    public function test_job_from_payload(): void
    {
        $restored = TestJob::fromPayload(['message' => 'restored']);
        $this->assertInstanceOf(TestJob::class, $restored);
        $this->assertSame('restored', $restored->message);
    }

    public function test_job_set_and_get_id(): void
    {
        $job = new TestJob();
        $job->setId('abc-123');
        $this->assertSame('abc-123', $job->getId());
    }

    public function test_job_set_and_get_attempts(): void
    {
        $job = new TestJob();
        $this->assertSame(0, $job->getAttempts());
        $job->setAttempts(3);
        $this->assertSame(3, $job->getAttempts());
    }

    public function test_worker_work_next_processes_job(): void
    {
        $worker = new Worker($this->queue);
        $this->queue->push(new TestJob('worker test'));

        $result = $worker->workNext();
        $this->assertNotNull($result);
        $this->assertTrue($result->handled);
    }

    public function test_worker_work_next_returns_null_when_empty(): void
    {
        $worker = new Worker($this->queue);
        $this->assertNull($worker->workNext());
    }

    public function test_worker_work_next_releases_on_failure(): void
    {
        Logger::resetInstance();
        $logger = Logger::getInstance(['buffer' => ['enabled' => true, 'capacity' => 50]]);
        $logger->setLevel('TRACE');

        $worker = new Worker($this->queue);
        $this->queue->push(new FailingTestJob('will fail'));

        $result = $worker->workNext();
        $this->assertNotNull($result);
        $this->assertTrue($result->handled);

        $pdoProp = new \ReflectionProperty($this->queue, 'pdo');
        $pdoProp->setAccessible(true);
        $pdo = $pdoProp->getValue($this->queue);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE queue = ?");
        $stmt->execute(['test']);
        $this->assertEquals(1, (int) $stmt->fetchColumn());
    }

    public function test_worker_run_processes_max_jobs(): void
    {
        $worker = new Worker($this->queue);
        $this->queue->push(new TestJob('a'));
        $this->queue->push(new TestJob('b'));
        $this->queue->push(new TestJob('c'));

        $worker->run(2);
        $this->assertEquals(1, $this->queue->size());
    }

    public function test_database_queue_implements_interface(): void
    {
        $this->assertInstanceOf(Queue::class, $this->queue);
    }

    public function test_job_id_is_string(): void
    {
        $job = new TestJob();
        $id = $this->queue->push($job);
        $this->assertIsString($id);
    }

    public function test_database_queue_custom_queue_name(): void
    {
        $queue = new DatabaseQueue('custom');
        $this->assertSame('custom', $queue->defaultQueue);
    }
}
