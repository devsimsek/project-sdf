# Queue System

The SDF queue system provides a unified interface for pushing and processing background jobs.

## Job Class

Extend `SDF\Queue\Job` and implement the `handle()` method:

```php
use SDF\Queue\Job;

class SendEmail extends Job {
    public function __construct(private string $email) {}

    public function handle(): void {
        // send email
    }
}
```

## Queue Interface

All queue drivers implement `SDF\Queue\QueueInterface`:

| Method       | Description                                  |
|-------------|----------------------------------------------|
| `push(Job $job)` | Push a job onto the queue                |
| `pop(): ?Job`    | Pop the next job from the queue         |
| `release(Job $job, int $delay = 0)` | Release a job back with delay |
| `delete(Job $job)` | Delete a completed job                |
| `size(): int`    | Get the queue size                      |
| `clear(): void`  | Clear all jobs from the queue           |

## DatabaseQueue

Uses Spark PDO to auto-create a `jobs` table on first use. No schema migrations needed.

```php
use SDF\Queue\DatabaseQueue;

$queue = new DatabaseQueue();
$queue->push(new SendEmail('user@example.com'));
$job = $queue->pop();
```

## RedisQueue

Requires the phpredis extension. Uses `RPUSH` for pushing and `BLPOP` with timeout for popping.

```php
use SDF\Queue\RedisQueue;

$queue = new RedisQueue($redis, 'jobs');
$queue->push(new SendEmail('user@example.com'));
```

## Worker

The `SDF\Queue\Worker` class processes jobs from a queue.

| Method                       | Description                                  |
|-----------------------------|----------------------------------------------|
| `work()`                    | Process jobs indefinitely (infinite loop)    |
| `workNext()`                | Process a single job                         |
| `run(int $maxJobs)`         | Process up to `$maxJobs` jobs                |

Failed jobs are automatically released back to the queue with a 5-second delay.

```php
use SDF\Queue\Worker;

$worker = new Worker($queue);
$worker->workNext();  // process one job
$worker->run(10);     // process 10 jobs
$worker->work();      // run forever
```

## Complete Example

```php
use SDF\Queue\Job;
use SDF\Queue\DatabaseQueue;
use SDF\Queue\Worker;

class SendEmail extends Job {
    public function __construct(private string $email) {}

    public function handle(): void {
        // send email logic
    }
}

$queue = new DatabaseQueue();
$queue->push(new SendEmail('user@example.com'));

$worker = new Worker($queue);
$worker->workNext();
```
