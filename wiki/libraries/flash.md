# SDF Flash Messages

## Overview

The `Flash` class provides one-time notification messages stored in the session. A flash message set in one request is available in the next and automatically consumed when retrieved. This is useful for redirect-based workflows (e.g., "Record saved" after a POST).

## How It Works

Flash messages use a two-bucket aging system:

| Bucket | Purpose |
|---|---|
| `_sdf_flash_new` | Messages set during the current request, intended for the **next** request |
| `_sdf_flash_cur` | Messages promoted from `_new` — available to **this** request |

On each `Flash` construction the aging cycle runs:
1. `_cur` is discarded (was for the previous request).
2. `_new` is promoted to `_cur` (now readable via `get()`).

## Class: `SDF\Flash`

### Constructor

```php
$flash = new SDF\Flash(?SDF\Session $session = null);
```

If no session is provided the global `Session` singleton is used.

### Methods

#### `set(string $key, mixed $value): self`

Store a flash message for the **next** request.

```php
// In a POST handler before redirect
$flash->set('success', 'User created successfully.');
$flash->set('errors', ['name' => 'Required']);
```

#### `get(string $key, mixed $default = null): mixed`

Retrieve a flash message. The message is **consumed** (removed from the session).

```php
// On the page after redirect
$message = $flash->get('success'); // 'User created successfully.'
$message = $flash->get('success'); // null (consumed)
```

#### `has(string $key): bool`

Check if a flash message exists without consuming it.

```php
if ($flash->has('errors')) {
    // show error block
}
```

#### `all(): array`

Return all flash messages without consuming them.

```php
$messages = $flash->all();
```

#### `keep(string $key): self`

Keep a flash message for one more request (prevents consumption during aging).

```php
$flash->keep('warning'); // survives another redirect
```

#### `now(string $key, mixed $value): self`

Set a flash message that is available **immediately** in the current request. It will not persist to the next request.

```php
$flash->now('info', 'This is shown right now.');
echo $flash->get('info'); // 'This is shown right now.'
```

#### `flash(string $key, mixed $value): self`

Alias of `set()`. Included for familiarity with other frameworks.

```php
$flash->flash('notice', 'Account updated.');
```

### In Controllers

```php
class UserController extends SDF\Controller
{
    public function store()
    {
        // ... validate and save ...

        $flash = new SDF\Flash();
        $flash->set('success', 'User saved.');
        $this->response->redirect('/users');
    }

    public function index()
    {
        $flash = new SDF\Flash();
        $data['message'] = $flash->get('success');
        $this->load->view('users/index', $data);
    }
}
```

## Testing

Flash messages rely on `$_SESSION`. In PHPUnit tests, start a session in `setUp`:

```php
protected function setUp(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
}
```
