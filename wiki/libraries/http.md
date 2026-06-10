# HTTP Messages (PSR-7)

SDF provides a **PSR-7** (`psr/http-message` ^2.0) implementation for HTTP messages, alongside the legacy mutable `SDF\Request` / `SDF\Response` classes.

## Installation

Already included - `psr/http-message` is installed via Composer in `composer.json`.

## Classes

All classes live in `SDF\Http\`:

| Class | Implements | Description |
|---|---|---|
| `Stream` | `StreamInterface` | Wraps a string or resource handle |
| `Uri` | `UriInterface` | Parses/constructs URIs; immutable `with*` |
| `UploadedFile` | `UploadedFileInterface` | Represents an uploaded file |
| `Response` | `ResponseInterface` | Immutable HTTP response |
| `ServerRequest` | `ServerRequestInterface` | Immutable server request; includes `fromGlobals()` factory |

## Usage

### Creating a ServerRequest from globals

```php
$request = \SDF\Http\ServerRequest::fromGlobals();

$method = $request->getMethod();          // "GET", "POST", etc.
$uri    = $request->getUri();             // UriInterface
$params = $request->getQueryParams();     // $_GET
$body   = $request->getParsedBody();      // parsed JSON or $_POST
$files  = $request->getUploadedFiles();   // UploadedFileInterface[]
$attrs  = $request->getAttributes();      // custom attributes
```

### Creating a PSR-7 response

```php
$response = new \SDF\Http\Response(
    200,                                  // status code
    ['Content-Type' => 'application/json'],
    new \SDF\Http\Stream(json_encode(['ok' => true])),
    '1.1',
);
```

### Immutable operations

All `with*` methods return a **new instance**:

```php
$req  = new \SDF\Http\ServerRequest('GET', '/');
$req2 = $req->withMethod('POST')
            ->withHeader('X-Custom', 'value')
            ->withAttribute('role', 'admin');

$req->getMethod();   // "GET" (unchanged)
$req2->getMethod();  // "POST"
```

### Attribute routing

Attributes are useful for carrying route-matched data:

```php
$request = $request
    ->withAttribute('route', 'user.show')
    ->withAttribute('params', ['id' => 5]);

$route = $request->getAttribute('route');
$id    = $request->getAttribute('params')['id'] ?? null;
```

### Working with uploaded files

```php
$files = $request->getUploadedFiles();
$avatar = $files['avatar'] ?? null;

if ($avatar && $avatar->getError() === UPLOAD_ERR_OK) {
    $stream = $avatar->getStream();
    // or
    $avatar->moveTo('/path/to/uploads/' . $avatar->getClientFilename());
}
```

## Legacy Bridge

The legacy `SDF\Request` and `SDF\Response` classes have `toPsr()` / `fromPsr()` methods:

```php
// Legacy → PSR-7
$psrRequest  = $request->toPsr();          // SDF\Request → ServerRequestInterface
$psrResponse = $response->toPsr();         // SDF\Response → ResponseInterface

// PSR-7 → Legacy
$legacyRequest  = \SDF\Request::fromPsr($psrRequest);
$legacyResponse = \SDF\Response::fromPsr($psrResponse);
```

> **Note:** `fromPsr` mutates PHP superglobals (`$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`) to match the PSR-7 request state.

## Requirements

- PHP 8.2+
- `psr/http-message` ^2.0 (Composer)
