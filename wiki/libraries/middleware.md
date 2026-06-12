# Middleware & Guards

Introduced in SDF v2.0.0. Middlewares filter requests before they hit your controller. Guards handle auth checks.

## Middleware Interface

All middlewares implement `SDF\Middleware`:

```php
<?php

namespace App\Middleware;

use SDF\Middleware;
use SDF\Request;

class JsonOnlyMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if ($request->header('Content-Type') !== 'application/json') {
            http_response_code(415);
            echo json_encode(['error' => 'JSON only']);
            return null;
        }
        return $next($request);
    }
}
```

## Using the Pipeline

```php
<?php

use SDF\Pipeline;

$response = (new Pipeline)
    ->send($request)
    ->through([
        App\Middleware\JsonOnlyMiddleware::class,
        App\Middleware\AuthMiddleware::class,
        App\Middleware\RateLimitMiddleware::class,
    ])
    ->then(function ($request) use ($controller, $method) {
        return $controller->$method();
    });
```

## Guards

Guards make authorization explicit. Extend `SDF\Guard`:

```php
<?php

namespace App\Guards;

use SDF\Guard;
use SDF\Request;

class AdminGuard extends Guard
{
    public function authorize(Request $request): bool
    {
        $user = $request->session('user');
        return isset($user) && $user['role'] === 'admin';
    }
}
```

Use in a controller:

```php
<?php

class AdminPanel extends SDF\Controller
{
    public function index(): void
    {
        $guard = new App\Guards\AdminGuard();
        if (!$guard->authorize($this->request)) {
            $this->response->status(403)->json(['error' => 'Forbidden']);
            return;
        }

        $this->fuse->render('admin/dashboard');
    }
}
```

## AuthMiddleware (built-in)

The framework ships with `SDF\Auth\AuthMiddleware`. It rejects unauthenticated requests with a 401 status:

```php
Router::middleware(\SDF\Auth\AuthMiddleware::class);
```

To use the JWT guard instead of sessions, subclass:

```php
<?php

namespace App\Middleware;

use SDF\Auth\AuthMiddleware;

class ApiAuthMiddleware extends AuthMiddleware
{
    public function __construct()
    {
        parent::__construct('jwt');
    }
}
```

See the [Auth documentation](auth.md) for full usage.

## CORS Middleware

The built-in `CorsMiddleware` handles cross-origin requests. Configured via `app/config/cors.php`:

```php
$config['cors'] = [
    'allowed_origins' => ['https://example.com'],  // '*' only when credentials=false
    'allowed_origins_patterns' => [],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'allow_credentials' => false,
];
```

When `allow_credentials` is `true`, wildcard origins (`*`) are rejected — you must specify explicit origins.

## Rate Limit Middleware

The built-in `RateLimitMiddleware` uses the Cache facade (60 req/min per IP+route by default). IP detection respects trusted proxies — configure via the `SDF_TRUSTED_PROXIES` server variable:

```php
// In index.php or .env
$_SERVER['SDF_TRUSTED_PROXIES'] = '10.0.0.1,10.0.0.2';
```

Untrusted `X-Forwarded-For` headers are ignored.

## Rate Limit Middleware Example

```php
<?php

namespace App\Middleware;

use SDF\Middleware;
use SDF\Request;

class RateLimitMiddleware implements Middleware
{
    private int $limit = 60;

    public function handle(Request $request, \Closure $next): mixed
    {
        $ip  = $_SERVER['REMOTE_ADDR'];
        $key = 'rate:' . $ip;

        $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.rate';
        $data = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : null;

        $now = time();
        if (!$data || ($now - $data['time']) > 60) {
            $data = ['count' => 1, 'time' => $now];
        } else {
            $data['count']++;
        }

        file_put_contents($cacheFile, json_encode($data));

        if ($data['count'] > $this->limit) {
            http_response_code(429);
            echo json_encode(['error' => 'Too Many Requests']);
            return null;
        }

        return $next($request);
    }
}
```
