# Logger

SDF provides a lightweight, pluggable `Logger` that centralizes application logging. The logger supports multiple levels (TRACE, DEBUG, INFO, WARN, ERROR, FATAL), contextual structured logging, markers (tags), and multiple handlers (console, in-memory buffer, file handlers). It's designed to be low-cost in the disabled-path via lazy evaluation.

Quick usage

- Static facade:

```php
<?php
use SDF\Logger;

Logger::info('User signed in', ['userId' => 42], 'AUTH');

// Lazy message evaluation (closure invoked only if level enabled)
Logger::debug(fn() => json_encode($expensiveData), ['userId' => 42]);
```

- Instance usage and configuration:

```php
<?php
// Initialize the singleton with optional configuration (first call only)
$cfg = [
  'level' => 'DEBUG',
  'console_color' => true,
  'buffer' => ['enabled' => true, 'capacity' => 500]
];
$logger = SDF\Logger::getInstance($cfg);

$logger->info('Started processing', ['requestId' => $rid]);

// Add a custom handler
$logger->addHandler(new SDF\ConsoleHandler(true));
```

Configuration

You can enable the in-memory circular buffer either via config or with the bootstrap constant `SDF_LOG_BUFFER`.

- `SDF_LOG_BUFFER = true` enables buffer with default capacity
- `SDF_LOG_BUFFER = 200` enables buffer with capacity 200

Alternatively pass configuration array to `Logger::getInstance($config)` as shown above.

Buffer API

```php
$buffer = Logger::getInstance()->getBufferHandler();
$recent = $buffer ? $buffer->search(fn($r) => $r->level === 'ERROR') : [];
```

Extensibility

Handlers implement a minimal interface (handle(LogRecord), flush()). You may implement additional handlers for file rotation, remote endpoints, or async batching and register them via `addHandler()`.

Design notes

- Messages may be strings or callables - callables are lazily evaluated for performance.
- Marker is an optional short tag useful for filtering and quick classification.
- ConsoleHandler produces colored output by default when TTY available. In daemonized environments you may prefer file handlers.

Web vs CLI

- When running in a web SAPI, the console handler writes to `php://stderr` to avoid polluting HTTP responses. Console output to STDOUT is used for CLI/daemon contexts only.

Operational notes

- RotatingFileHandler supports optional compression when zlib is available. If the `gzencode()` function is not present (no ext-zlib), rotation will fall back to uncompressed files. Ensure the zlib extension is available if you rely on compressed rotated logs.

Testing

- The logger exposes `resetInstance()` to reset the singleton so unit tests can create a fresh configured instance.

Examples and API reference are available in the code comments inside `sdf/core/Logger.php`.
