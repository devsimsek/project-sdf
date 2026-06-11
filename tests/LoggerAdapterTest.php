<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Log\LoggerAdapter;
use SDF\Logger;

class LoggerAdapterTest extends TestCase
{
    private LoggerAdapter $adapter;
    private Logger $logger;

    protected function setUp(): void
    {
        Logger::resetInstance();
        $this->logger = Logger::getInstance(['buffer' => ['enabled' => true, 'capacity' => 100]]);
        $this->logger->setLevel('TRACE');
        $this->adapter = new LoggerAdapter($this->logger);
    }

    public function test_emergency_logs_as_fatal(): void
    {
        $this->adapter->emergency('System down');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'FATAL' && str_contains($r->message, 'System down'));
        $this->assertCount(1, $records);
    }

    public function test_alert_logs_as_fatal(): void
    {
        $this->adapter->alert('Alert message');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'FATAL');
        $this->assertCount(1, $records);
    }

    public function test_critical_logs_as_error(): void
    {
        $this->adapter->critical('Critical error');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'ERROR');
        $this->assertCount(1, $records);
    }

    public function test_error_logs_as_error(): void
    {
        $this->adapter->error('Error occurred');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'ERROR');
        $this->assertCount(1, $records);
    }

    public function test_warning_logs_as_warn(): void
    {
        $this->adapter->warning('Warning message');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'WARN');
        $this->assertCount(1, $records);
    }

    public function test_notice_logs_as_info(): void
    {
        $this->adapter->notice('Notice message');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'INFO');
        $this->assertCount(1, $records);
    }

    public function test_info_logs_as_info(): void
    {
        $this->adapter->info('Info message');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'INFO');
        $this->assertCount(1, $records);
    }

    public function test_debug_logs_as_debug(): void
    {
        $this->adapter->debug('Debug message');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'DEBUG');
        $this->assertCount(1, $records);
    }

    public function test_log_with_context(): void
    {
        $this->adapter->info('User {action}', ['action' => 'login']);
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => str_contains($r->message, '{action}'));
        $this->assertCount(1, $records);
        $this->assertSame(['action' => 'login'], $records[0]->context);
    }

    public function test_log_with_custom_logger(): void
    {
        $customLogger = Logger::getInstance(['buffer' => ['enabled' => true, 'capacity' => 50]]);
        $customLogger->setLevel('TRACE');
        $adapter = new LoggerAdapter($customLogger);
        $adapter->warning('Custom logger test');
        $buffer = $customLogger->getBufferHandler();
        $this->assertNotNull($buffer);
        $records = $buffer->search(fn($r) => $r->level === 'WARN');
        $this->assertCount(1, $records);
    }

    public function test_unknown_level_falls_back_to_info(): void
    {
        $this->adapter->log('unknown_level', 'Fallback test');
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => $r->level === 'INFO');
        $this->assertCount(1, $records);
    }

    public function test_log_with_stringable_message(): void
    {
        $msg = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };
        $this->adapter->info($msg);
        $buffer = $this->logger->getBufferHandler();
        $records = $buffer?->search(fn($r) => str_contains($r->message, 'Stringable message'));
        $this->assertCount(1, $records);
    }

    public function test_psr3_interface_compliance(): void
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->adapter);
        $this->assertInstanceOf(\Psr\Log\AbstractLogger::class, $this->adapter);
    }
}
