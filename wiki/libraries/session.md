# SDF Session

## Overview

The `Session` class provides a fluent interface over PHP's native session handling. It is a PSR-4 core class in the `SDF` namespace and is auto-started in `sdf/__init.php` so a session is always available.

## Class: `SDF\Session`

### Singleton Access

```php
$session = SDF\Session::getInstance();
```

The singleton is automatically created during framework bootstrap. Controllers may also construct a fresh instance:

```php
$session = new SDF\Session();
```

### Methods

#### `set(string $key, mixed $value): self`

Store a value in the session.

```php
$session->set('user_id', 42);
$session->set('cart', ['item' => 1, 'qty' => 3]);
```

#### `get(string $key, mixed $default = null): mixed`

Retrieve a value from the session.

```php
$userId = $session->get('user_id');
$name   = $session->get('name', 'Guest');
```

#### `has(string $key): bool`

Check if a key exists in the session.

```php
if ($session->has('user_id')) {
    // user is logged in
}
```

#### `remove(string $key): void`

Remove a single key from the session.

```php
$session->remove('temp_data');
```

#### `clear(): void`

Remove all session data.

```php
$session->clear();
```

#### `id(): string|false`

Return the current session ID.

```php
$id = $session->id();
```

#### `regenerate(bool $deleteOld = false): self`

Regenerate the session ID (recommended after login for session fixation protection).

```php
$session->regenerate(true);
```

#### `destroy(): void`

Destroy the session and all its data.

```php
$session->destroy();
```

### Constructor Parameters

```php
new SDF\Session(?string $cacheExpire = null, ?string $cacheLimiter = null);
```

- `$cacheExpire` — Session cache expiration in minutes (sets `session_cache_expire()`).
- `$cacheLimiter` — Session cache limiter header value (sets `session_cache_limiter()`).

### In Controllers

```php
class HomeController extends SDF\Controller
{
    public function index()
    {
        $session = SDF\Session::getInstance();
        $session->set('visited', true);
        // ...
    }
}
```
