<?php

namespace SDF;

use Throwable;


/**
 * Single-file Logger implementation that contains Level, LogRecord,
 * HandlerInterface, ConsoleHandler, BufferHandler and Logger facade/engine.
 */
class Level
{
    public const TRACE = "TRACE";
    public const DEBUG = "DEBUG";
    public const INFO = "INFO";
    public const WARN = "WARN";
    public const ERROR = "ERROR";
    public const FATAL = "FATAL";

    private const MAP = [
        self::TRACE => 10,
        self::DEBUG => 20,
        self::INFO => 30,
        self::WARN => 40,
        self::ERROR => 50,
        self::FATAL => 60,
    ];

    public static function toInt(string $level): int
    {
        return self::MAP[strtoupper($level)] ?? 0;
    }

    public static function isValid(string $level): bool
    {
        return array_key_exists(strtoupper($level), self::MAP);
    }
}

/**
 * Structured log record
 */
class LogRecord
{
    public int $timestamp;
    public int $levelInt;

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string|null $marker
     * @param int|null $timestamp milliseconds since epoch
     */
    public function __construct(
        public string $level,
        public string $message,
        public array $context = [],
        public ?string $marker = null,
        ?int $timestamp = null,
    ) {
        $this->timestamp = $timestamp ?? (int) (microtime(true) * 1000);
        $this->levelInt = Level::toInt($level);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            "ts" => $this->timestamp,
            "level" => $this->level,
            "levelInt" => $this->levelInt,
            "message" => $this->message,
            "context" => $this->context,
            "marker" => $this->marker,
        ];
    }
}

interface HandlerInterface
{
    public function handle(LogRecord $record): void;

    public function flush(): void;
}

class ConsoleHandler implements HandlerInterface
{
    private array $levelColors = [
        Level::TRACE => "\033[37m", // white/grey
        Level::DEBUG => "\033[36m", // cyan
        Level::INFO => "\033[32m", // green
        Level::WARN => "\033[33m", // yellow
        Level::ERROR => "\033[31m", // red
        Level::FATAL => "\033[1;31m", // bold red
    ];

    private bool $useColor;

    public function __construct(bool $useColor = true)
    {
        $this->useColor = $useColor && $this->isTty();
    }

    public function handle(LogRecord $record): void
    {
        $line = $this->format($record);
        // Choose appropriate output stream: CLI -> STDOUT, Web -> STDERR (avoid polluting response body)
        if (PHP_SAPI === 'cli' && defined('STDOUT')) {
            $out = \STDOUT;
            $shouldClose = false;
        } else {
            $out = fopen('php://stderr', 'w');
            $shouldClose = true;
        }

        if ($this->useColor && PHP_SAPI === 'cli') {
            $color = $this->levelColors[$record->level] ?? "\033[0m";
            $reset = "\033[0m";
            fwrite($out, $color . $line . $reset . PHP_EOL);
        } else {
            fwrite($out, $line . PHP_EOL);
        }

        if ($shouldClose) {
            fclose($out);
        }
    }

    public function flush(): void
    {
        // nothing to flush for console
    }

    protected function format(LogRecord $r): string
    {
        $ts = date("Y-m-d H:i:s", (int) ($r->timestamp / 1000));
        $marker = $r->marker ? " [$r->marker]" : "";
        $ctx = $r->context
            ? json_encode($r->context, JSON_UNESCAPED_SLASHES)
            : "";
        return sprintf(
            "%s %s%s: %s %s",
            $ts,
            $r->level,
            $marker,
            $r->message,
            $ctx,
        );
    }

    // todo: add other methods
    private function isTty(): bool
    {
        // simple heuristic: only attempt posix_isatty when available and STDOUT exists
        return function_exists("posix_isatty") && defined('STDOUT') && posix_isatty(\STDOUT);
    }
}

class FileHandler implements HandlerInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public function handle(LogRecord $record): void
    {
        $line = $this->format($record) . PHP_EOL;
        // write with exclusive lock
        $fp = @fopen($this->path, "a");
        if (!$fp) {
            return;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function flush(): void
    {
        // nothing for basic file handler
    }

    protected function format(LogRecord $r): string
    {
        // simple JSON line for now
        return json_encode($r->toArray(), JSON_UNESCAPED_SLASHES);
    }
}

class RotatingFileHandler implements HandlerInterface
{
    private string $path;
    private int $maxBytes;
    private int $maxFiles;
    private bool $compress;

    public function __construct(
        string $path,
        int $maxBytes,
        int $maxFiles = 5,
        bool $compress = false,
    ) {
        $this->path = $path;
        $this->maxBytes = max(1024, $maxBytes);
        $this->maxFiles = max(1, $maxFiles);
        $this->compress = $compress;

        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public function handle(LogRecord $record): void
    {
        // rotate if necessary
        clearstatcache(true, $this->path);
        if (
            file_exists($this->path) &&
            filesize($this->path) >= $this->maxBytes
        ) {
            $this->rotate();
        }
        // delegate to basic write todo: improve
        $line = $this->format($record) . PHP_EOL;
        $fp = @fopen($this->path, "a");
        if (!$fp) {
            return;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    private function rotate(): void
    {
        // delete oldest if exists
        $last = $this->path . "." . $this->maxFiles;
        if (file_exists($last)) {
            @unlink($last);
        }
        // shift files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $src = $this->path . "." . $i;
            $dst = $this->path . "." . ($i + 1);
            if (file_exists($src)) {
                @rename($src, $dst);
            }
        }
        // rotate current to .1
        if (file_exists($this->path)) {
            $first = $this->path . ".1";
            @rename($this->path, $first);
            if ($this->compress && file_exists($first)) {
                // compress file to .gz
                $data = file_get_contents($first);
                if ($data !== false) {
                    file_put_contents($first . ".gz", gzencode($data));
                    @unlink($first);
                }
            }
        }
    }

    public function flush(): void
    {
        // nothing specific
    }

    protected function format(LogRecord $r): string
    {
        return json_encode($r->toArray(), JSON_UNESCAPED_SLASHES);
    }
}

class AsyncHandler implements HandlerInterface
{
    private HandlerInterface $inner;
    private int $batchSize;
    /** @var LogRecord[] */
    private array $batch = [];
    private bool $useFork;

    public function __construct(
        HandlerInterface $inner,
        int $batchSize = 50,
        bool $useFork = false,
    ) {
        $this->inner = $inner;
        $this->batchSize = max(1, $batchSize);
        $this->useFork = $useFork && function_exists("pcntl_fork");

        // ensure flush on shutdown
        register_shutdown_function([$this, "flush"]);
    }

    public function handle(LogRecord $record): void
    {
        $this->batch[] = $record;
        if (count($this->batch) >= $this->batchSize) {
            $this->flushBatch();
        }
    }

    private function flushBatch(): void
    {
        if (empty($this->batch)) {
            return;
        }
        $batch = $this->batch;
        $this->batch = [];

        if ($this->useFork) {
            // attempt to fork and let child write
            $pid = pcntl_fork();
            if ($pid === -1) {
                // fallback to sync
                foreach ($batch as $r) {
                    $this->inner->handle($r);
                }
                return;
            }
            if ($pid === 0) {
                // child
                foreach ($batch as $r) {
                    $this->inner->handle($r);
                }
                // necessary to exit child process
                exit(0);
            }
            // parent continues
            return;
        }

        // synchronous batch write
        foreach ($batch as $r) {
            $this->inner->handle($r);
        }
    }

    public function flush(): void
    {
        $this->flushBatch();
        try {
            $this->inner->flush();
        } catch (Throwable) {
        }
    }
}

class BufferHandler implements HandlerInterface
{
    /** @var LogRecord[] */
    private array $buffer = [];
    private int $capacity;

    public function __construct(int $capacity = 500)
    {
        $this->capacity = max(1, $capacity);
    }

    public function handle(LogRecord $record): void
    {
        if (count($this->buffer) >= $this->capacity) {
            array_shift($this->buffer);
        }
        $this->buffer[] = $record;
    }

    public function flush(): void
    {
        // no-op for in-memory
    }

    /**
     * Search the buffer with a simple predicate.
     * @param callable|null $predicate returns true to include record
     * @return LogRecord[]
     */
    public function search(?callable $predicate = null): array
    {
        if ($predicate === null) {
            return $this->buffer;
        }
        return array_values(array_filter($this->buffer, $predicate));
    }
}

/**
 * SDF Logger
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Logger.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#logger
 * @since       v1.0
 * @filesource
 */
class Logger
{
    private static ?self $instance = null;

    /** @var HandlerInterface[] */
    private array $handlers = [];

    private int $levelThreshold;

    private ?BufferHandler $bufferHandler = null;

    /**
     * Internal constructor
     *
     * @param array $config Logger configuration: 'level', 'console_color', 'buffer' => ['enabled'=>bool, 'capacity'=>int]
     */
    private function __construct(array $config = [])
    {
        // Determine default level based on environment if not provided
        if (isset($config["level"])) {
            $this->levelThreshold = Level::toInt($config["level"]);
        } else {
            $env = defined("SDF_ENV") ? strtolower(SDF_ENV) : "production";
            if (in_array($env, ["dev", "development", "local"])) {
                $this->levelThreshold = Level::toInt(Level::DEBUG);
            } else {
                $this->levelThreshold = Level::toInt(Level::INFO);
            }
        }

        // default handlers
        $useColor = $config["console_color"] ?? true;
        $this->handlers[] = new ConsoleHandler($useColor);

        // file handler if configured (support rotation and async)
        $fileCfg = $config["file"] ?? null;
        if (is_array($fileCfg) && !empty($fileCfg["path"])) {
            $path = $fileCfg["path"];
            $rotateSize = (int) ($fileCfg["rotate_size"] ?? 0);
            $maxFiles = (int) ($fileCfg["max_files"] ?? 0);
            $asyncCfg = $config["async"] ?? [];
            $useAsync = (bool) ($asyncCfg["enabled"] ?? false);
            $batchSize = (int) ($asyncCfg["batch_size"] ?? 50);

            if ($rotateSize > 0 && $maxFiles > 0) {
                $fileHandler = new RotatingFileHandler(
                    $path,
                    $rotateSize,
                    $maxFiles,
                    (bool) ($fileCfg["compress"] ?? false),
                );
            } else {
                $fileHandler = new FileHandler($path);
            }

            if ($useAsync) {
                $this->handlers[] = new AsyncHandler($fileHandler, $batchSize);
            } else {
                $this->handlers[] = $fileHandler;
            }
        }

        // buffer enabled via config or SDF_LOG_BUFFER
        $bufferCfg = $config["buffer"] ?? null;
        if (defined("SDF_LOG_BUFFER")) {
            $b = constant("SDF_LOG_BUFFER");
            if (is_int($b) && $b > 0) {
                $this->bufferHandler = new BufferHandler($b);
                $this->handlers[] = $this->bufferHandler;
            } elseif ($b) {
                $this->bufferHandler = new BufferHandler(500);
                $this->handlers[] = $this->bufferHandler;
            }
        } elseif (is_array($bufferCfg) && ($bufferCfg["enabled"] ?? false)) {
            $cap = (int) ($bufferCfg["capacity"] ?? 500);
            $this->bufferHandler = new BufferHandler($cap);
            $this->handlers[] = $this->bufferHandler;
        }
    }

    /**
     * Singleton access.
     *
     * @param array $config optional configuration used on first initialization
     * @return self
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Reset singleton instance (useful for unit tests)
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Log a message via the singleton instance.
     *
     * @param string $level
     * @param string|callable $message string or lazy callable returning string
     * @param array $context context key/values
     * @param string|null $marker optional tag
     * @return void
     */
    public static function log(
        string $level,
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        $inst = self::getInstance();
        $inst->logInstance($level, $message, $context, $marker);
    }

    /**
     * Convenience wrappers (see log())
     */
    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function trace(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::TRACE, $message, $context, $marker);
    }

    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function debug(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::DEBUG, $message, $context, $marker);
    }

    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function info(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::INFO, $message, $context, $marker);
    }

    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function warn(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::WARN, $message, $context, $marker);
    }

    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function error(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::ERROR, $message, $context, $marker);
    }

    /** @param string|callable $message
     * @param array $context
     * @param string|null $marker
     */
    public static function fatal(
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        self::log(Level::FATAL, $message, $context, $marker);
    }

    /**
     * Instance-based logging method.
     *
     * @param string $level
     * @param string|callable $message
     * @param array $context
     * @param string|null $marker
     * @return void
     */
    public function logInstance(
        string $level,
        string|callable $message,
        array $context = [],
        ?string $marker = null,
    ): void {
        // validate
        if (!Level::isValid($level)) {
            $level = Level::INFO;
        }

        $lvlInt = Level::toInt($level);
        if ($lvlInt < $this->levelThreshold) {
            // skip quickly if below threshold
            return;
        }

        // lazy message evaluation
        $msg = is_callable($message) ? $message() : $message;

        $record = new LogRecord($level, (string) $msg, $context, $marker);

        foreach ($this->handlers as $h) {
            try {
                $h->handle($record);
            } catch (Throwable $e) {
                // todo: switch to a env aware disregard/swallow
                // swallow handler exceptions to avoid breaking app
            }
        }
    }

    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function setLevel(string $level): void
    {
        $this->levelThreshold = Level::toInt($level);
    }

    public function isLevelEnabled(string $level): bool
    {
        return Level::toInt($level) >= $this->levelThreshold;
    }

    public function getBufferHandler(): ?BufferHandler
    {
        return $this->bufferHandler;
    }

    public function flush(): void
    {
        foreach ($this->handlers as $h) {
            try {
                $h->flush();
            } catch (Throwable) {
            }
        }
    }
}
