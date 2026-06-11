<?php

declare(strict_types=1);

/**
 * smskSoft SDF PSR-3 Logger Adapter
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Log
 * @file        LoggerAdapter.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/log
 * @since       v1.0
 * @filesource
 */

namespace SDF\Log;

use Psr\Log\AbstractLogger;
use SDF\Logger;

/**
 * Thin PSR-3 wrapper around SDF\Logger.
 *
 * Maps PSR-3 log levels to SDF log levels and delegates to the singleton Logger.
 */
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

    /**
     * @param Logger|null $logger Optional logger instance; defaults to singleton.
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? Logger::getInstance();
    }

    /**
     * Log a message with a PSR-3 level.
     *
     * @param mixed  $level   PSR-3 log level string.
     * @param string|\Stringable $message
     * @param array  $context
     * @return void
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $sdfLevel = self::$levelMap[$level] ?? 'INFO';
        $this->logger->logInstance($sdfLevel, (string) $message, $context);
    }
}
