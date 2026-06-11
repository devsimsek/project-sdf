# PSR-3 Logger Adapter

The `SDF\Log\LoggerAdapter` wraps the existing `SDF\Logger` with a PSR-3-compatible interface.

```php
use SDF\Log\LoggerAdapter;
use Psr\Log\LoggerInterface;

$logger = new LoggerAdapter();
$logger->info('User registered', ['id' => 42]);
$logger->error('Database connection failed', ['host' => $host]);
```

## Level mapping

| PSR-3 | SDF |
|-------|-----|
| emergency, alert | FATAL |
| critical, error | ERROR |
| warning | WARN |
| notice, info | INFO |
| debug | DEBUG |
