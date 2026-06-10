# Tutorial: Custom Middleware

Build three practical middlewares: rate limiting (via Cache facade), CORS headers, and request logging.

---

## Middleware Structure

All middlewares implement `SDF\Middleware` and live in `app/middlewares/`.

```
app/middlewares/
├── RateLimitMiddleware.php
├── CorsMiddleware.php
└── LogMiddleware.php
```

---

## 1. CORS Middleware

Adds CORS headers so browser clients on other origins can reach your API.

`app/middlewares/CorsMiddleware.php`:

```php
<?php

use SDF\Middleware;
use SDF\Request;

class CorsMiddleware implements Middleware
{
    private array $allowedOrigins;

    public function __construct(array $allowedOrigins = ['*'])
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array('*', $this->allowedOrigins, true) || in_array($origin, $this->allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return $next($request);
    }
}
```

> **PSR-7/PSR-15 alternative:** For a framework-agnostic middleware pipeline, implement `Psr\Http\Server\MiddlewareInterface`
> and use `ServerRequestInterface`/`ResponseInterface` instead of reading `$_SERVER` superglobals.

---

## 2. Rate Limit Middleware

Limits each IP to N requests per minute using the **Cache facade** - swap drivers (`file`/`redis`/`memcached`) via config without changing code.

`app/middlewares/RateLimitMiddleware.php`:

```php
<?php

use SDF\Cache\Cache;
use SDF\Middleware;
use SDF\Request;

class RateLimitMiddleware implements Middleware
{
    public function __construct(private int $limit = 60) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'rate_limit:' . md5($ip);

        $data = Cache::get($key, ['count' => 0, 'window' => time()]);

        $now = time();

        if (($now - $data['window']) >= 60) {
            $data = ['count' => 1, 'window' => $now];
        } else {
            $data['count']++;
        }

        Cache::set($key, $data, 120);

        header('X-RateLimit-Limit: ' . $this->limit);
        header('X-RateLimit-Remaining: ' . max(0, $this->limit - $data['count']));

        if ($data['count'] > $this->limit) {
            http_response_code(429);
            header('Retry-After: ' . (60 - ($now - $data['window'])));
            echo json_encode(['error' => 'Too Many Requests']);
            return null;
        }

        return $next($request);
    }
}
```

> **Why Cache?** The `Cache` facade abstracts file/Redis/Memcached behind the same API.
> Switch to Redis in production just by changing `app/config/cache.php` - no code changes.

---

## 3. Request Log Middleware

Logs every request to `storage/logs/requests.log`.

`app/middlewares/LogMiddleware.php`:

```php
<?php

use SDF\Middleware;
use SDF\Request;

class LogMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $start = microtime(true);
        $result = $next($request);
        $ms    = round((microtime(true) - $start) * 1000, 3);

        $line = sprintf(
            "[%s] %s %s - %sms from %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $ms,
            $_SERVER['REMOTE_ADDR'] ?? '-'
        );

        $logDir = 'storage/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . 'requests.log', $line, FILE_APPEND | LOCK_EX);

        return $result;
    }
}
```

---

## 4. Using the Pipeline

Wire all three together in a controller or bootstrap:

```php
<?php

use SDF\Pipeline;

$response = (new Pipeline)
    ->send($request)
    ->through([
        CorsMiddleware::class,
        RateLimitMiddleware::class,
        LogMiddleware::class,
    ])
    ->then(function ($request) use ($controller, $method, $params) {
        return $controller->$method(...$params);
    });
```

---

## What You Learned

- Implementing the `SDF\Middleware` interface
- CORS preflight handling
- Rate limiting with the `Cache` facade (driver-agnostic)
- Measuring and logging response times via middleware wrap
- Chaining multiple middlewares in `Pipeline::through()`
- The built-in `SDF\Auth\AuthMiddleware` for route authentication
