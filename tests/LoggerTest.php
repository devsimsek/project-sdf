<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Logger;
use SDF\BufferHandler;

class LoggerTest extends TestCase
{
    protected function tearDown(): void
    {
        // reset singleton between tests
        Logger::resetInstance();
        parent::tearDown();
    }

    public function test_lazy_evaluation_not_called_when_level_disabled(): void
    {
        Logger::resetInstance();
        $logger = Logger::getInstance(['level' => 'INFO', 'buffer' => ['enabled' => false]]);

        $called = false;
        Logger::debug(function () use (&$called) {
            $called = true;
            return 'should not be computed';
        });

        $this->assertFalse($called, 'Debug closure must not be invoked when level disabled.');
    }

    public function test_lazy_evaluation_called_when_level_enabled(): void
    {
        Logger::resetInstance();
        $logger = Logger::getInstance(['level' => 'DEBUG', 'buffer' => ['enabled' => false]]);

        $called = false;
        Logger::debug(function () use (&$called) {
            $called = true;
            return 'computed';
        });

        $this->assertTrue($called, 'Debug closure should be invoked when level enabled.');
    }

    public function test_buffer_collects_messages_and_rolls_over(): void
    {
        Logger::resetInstance();
        $logger = Logger::getInstance(['level' => 'DEBUG', 'buffer' => ['enabled' => true, 'capacity' => 3]]);

        Logger::info('one');
        Logger::info('two');
        Logger::info('three');
        Logger::info('four');

        $buffer = $logger->getBufferHandler();
        $this->assertInstanceOf(BufferHandler::class, $buffer);

        $records = $buffer->search();
        $this->assertCount(3, $records);
        $this->assertSame('two', $records[0]->message);
        $this->assertSame('three', $records[1]->message);
        $this->assertSame('four', $records[2]->message);
    }

    public function test_level_filtering_prevents_lower_messages(): void
    {
        Logger::resetInstance();
        $logger = Logger::getInstance(['level' => 'WARN', 'buffer' => ['enabled' => true, 'capacity' => 10]]);

        Logger::info('info');
        Logger::warn('warn');
        Logger::error('err');

        $buffer = $logger->getBufferHandler();
        $records = $buffer->search();

        $this->assertCount(2, $records);
        $this->assertSame('warn', $records[0]->message);
        $this->assertSame('err', $records[1]->message);
    }
}
