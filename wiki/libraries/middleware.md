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

## Real-World Auth Middleware

```php
<?php

namespace App\Middleware;

use SDF\Middleware;
use SDF\Request;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return null;
        }

        $jwt = substr($token, 7);
        // TODO: validate JWT via SDF-9 Auth layer
        $request->set('user_jwt', $jwt);

        return $next($request);
    }
}
```

## Rate Limit Middleware Example

```php
<?php

namespace App\Middleware;

use SDF\Middleware;
use SDF\Request;

class RateLimitMiddleware implements Middleware
{
    private int $limit = 60;  // requests per minute

    public function handle(Request $request, \Closure $next): mixed
    {
        $ip  = $_SERVER['REMOTE_ADDR'];
        $key = 'rate:' . $ip;

        // Simple file-based counter (replace with Redis in prod)
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
