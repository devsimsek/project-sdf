# SDF Auth

## Overview

The `Auth` system provides first-class authentication with two built-in guards:

| Guard | Namespace | Use Case |
|---|---|---|
| **SessionGuard** | `SDF\Auth\SessionGuard` | Classic PHP session auth (web apps) |
| **JwtGuard** | `SDF\Auth\JwtGuard` | Stateless JWT via `Authorization: Bearer` (APIs) |

A unified facade (`SDF\Auth\Auth`) delegates to the active guard so your code stays the same regardless of which guard is configured.

## Configuration

Create `app/config/auth.php`:

```php
<?php

$config['auth'] = [
    'default' => 'session',
    'guards' => [
        'session' => [
            'provider' => 'users',
        ],
        'jwt' => [
            'provider' => 'users',
            'secret' => 'your-256-bit-secret',
            'ttl' => 3600,
            'refresh_ttl' => 604800,
        ],
    ],
    'providers' => [
        'users' => [
            'model' => \App\Models\User::class,
        ],
    ],
];
```

### Options

| Option | Default | Description |
|---|---|---|
| `default` | `session` | Active guard (`session` or `jwt`) |
| `guards.jwt.secret` | - | HMAC-SHA256 secret key for signing JWTs |
| `guards.jwt.ttl` | `3600` | Access token lifetime in seconds |
| `guards.jwt.refresh_ttl` | `604800` | Refresh token lifetime in seconds (7 days) |
| `providers.*.model` | `\App\Models\User::class` | User model class with static `find()` and `where()` |

## User Model

The user model must expose:
- A public `$id` property (primary key)
- A public `$password` property (bcrypt hash)
- Static `find($id): ?object`
- Static `where(string $column, mixed $value): object` returning a query builder with `->first(): ?object`

If you use Spark ORM, your model already satisfies this contract:

```php
<?php

namespace App\Models;

use SDF\Spark\Model;

class User extends Model
{
    protected static string $table = 'users';
}
```

## Facade Usage

```php
use SDF\Auth\Auth;
use SDF\Auth\Auth;

// Check authentication
if (Auth::check()) {
    echo 'Logged in';
}

// Get authenticated user
$user = Auth::user();

// Login
$user = User::find(1);
Auth::login($user);

// Attempt with credentials
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    // authenticated
}

// Logout
Auth::logout();

// Switch guard
Auth::guard('jwt')->check();
```

## Session Guard

Stores the user ID in `$_SESSION['_auth_id']` and hydrates the full model on each request.

```php
use SDF\Auth\SessionGuard;
use SDF\Auth\UserProvider;

$provider = new UserProvider(\App\Models\User::class);
$guard = new SessionGuard($provider);

$guard->login($user);
$guard->check();    // true
$guard->user();     // User model
$guard->logout();
```

## JWT Guard

Reads the `Authorization: Bearer <token>` header, decodes and verifies the JWT, and hydrates the user.

```php
use SDF\Auth\JwtGuard;
use SDF\Auth\UserProvider;
use SDF\Request;

$provider = new UserProvider(\App\Models\User::class);
$guard = new JwtGuard($provider, new Request(), 'your-secret');

$user = \App\Models\User::find(1);
$token = $guard->issueToken($user);

// Guard reads bearer token automatically
$guard->check(); // true
```

### Token Refresh

```php
// Issue a refresh token alongside the access token
$accessToken  = $guard->issueToken($user);
$refreshToken = $guard->issueRefreshToken($user);

// Later, when the access token expires:
$result = $guard->refresh($refreshToken);
// Returns: ['access_token' => '...', 'refresh_token' => '...', 'token_type' => 'Bearer', 'expires_in' => 3600]

// Or via the facade:
$result = Auth::refresh($refreshToken);
```

### Via Facade

```php
Auth::guard('jwt')->issueToken($user);
Auth::guard('jwt')->refresh($refreshToken);
```

## AuthMiddleware

Rejects unauthenticated requests with a `401` HTTP status.

```php
Router::middleware(\SDF\Auth\AuthMiddleware::class);
```

All routes registered after this middleware require authentication. To protect only specific routes, register the middleware in a route group or use per-route middleware arrays (not yet supported by the Router - subclass or customise as needed).

## Testing

The `Auth` facade provides `setGuard()` and `reset()` for swapping guards in tests:

```php
use SDF\Auth\Auth;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        Auth::reset();
    }

    public function test_something(): void
    {
        // Swap in a mock guard
        $mock = $this->createMock(\SDF\Auth\Guard::class);
        $mock->method('check')->willReturn(true);
        Auth::setGuard('session', $mock);

        $this->assertTrue(Auth::check());
    }
}
```
