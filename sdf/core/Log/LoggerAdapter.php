<?php

declare(strict_types=1);

namespace SDF\Log;

use Psr\Log\AbstractLogger;
use SDF\Logger;
use SDF\Level;

class LoggerAdapter extends AbstractLogger
{
    private static array $levelMap = [
        'emergency' => 'FATAL',
        'alert' => 'FATAL',
        'critical' => 'ERROR',
        'error' => 'ERROR',
        'warning' => 'WARN',
        'notice' => 'INFO',
        'info' => 'INFO',
        'debug' => 'DEBUG',
    ];

    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? Logger::getInstance();
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $sdfLevel = self::$levelMap[$level] ?? 'INFO';
        $this->logger->logInstance($sdfLevel, (string) $message, $context);
    }
}
