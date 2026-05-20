<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Level;
use SDF\LogRecord;
use SDF\Logger;

class LoggerInternalsTest extends TestCase
{
    protected function tearDown(): void
    {
        Logger::resetInstance();
        parent::tearDown();
    }

    public function test_level_mapping_and_validation(): void
    {
        $this->assertSame(20, Level::toInt('debug'));
        $this->assertSame(60, Level::toInt('FATAL'));
        $this->assertTrue(Level::isValid('warn'));
        $this->assertFalse(Level::isValid('unknown'));
    }

    public function test_log_record_exports_expected_array(): void
    {
        $record = new LogRecord(Level::ERROR, 'boom', ['id' => 7], 'marker', 123);

        $this->assertSame([
            'ts' => 123,
            'level' => Level::ERROR,
            'levelInt' => 50,
            'message' => 'boom',
            'context' => ['id' => 7],
            'marker' => 'marker',
        ], $record->toArray());
    }

    public function test_logger_level_threshold_can_be_changed(): void
    {
        $logger = Logger::getInstance(['level' => 'ERROR', 'buffer' => ['enabled' => true, 'capacity' => 2]]);

        $this->assertFalse($logger->isLevelEnabled(Level::WARN));
        $logger->setLevel(Level::DEBUG);
        $this->assertTrue($logger->isLevelEnabled(Level::INFO));
    }
}
