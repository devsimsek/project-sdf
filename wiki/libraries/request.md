# SDF Request Documentation

## Overview

The `Request` class is part of the **SDF Core** library and provides methods to interact with various PHP superglobal arrays, such as `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_SESSION`, `$_COOKIE`, and `$_FILES`. It also offers methods to inspect the HTTP method, URI, headers, client info, and input data including JSON body parsing and bearer token extraction.

## Class: `Request`

This class uses the `CoreUtilities` trait and handles HTTP request inspection and data retrieval.

Loaded by the `Controller` class and accessed via `$this->request`.

### Input Methods

#### `get(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_GET` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_GET` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

- **Example:**
  ```php
  $this->request->get('page', 1);
  ```

#### `post(?string $key, mixed $default = null): mixed`

Retrieves POST data. For `application/json` content-types, parses the JSON body. For standard form submissions, reads from `$_POST`. Pass `null` as key to retrieve all parsed data.

- **Parameters:**
  - `string|null $key`: The key to retrieve, or `null` for all data.
  - `mixed $default`: The default value if the key does not exist (default is `null`).

- **Returns:** `mixed`

- **Example:**
  ```php
  $name = $this->request->post('name');
  $all  = $this->request->post(null);
  ```

#### `request(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_REQUEST` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_REQUEST` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

#### `server(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_SERVER` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_SERVER` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

#### `session(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_SESSION` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_SESSION` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

#### `cookie(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_COOKIE` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_COOKIE` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

#### `file(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_FILES` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_FILES` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

#### `query(?string $key = null, mixed $default = null): mixed`

Get query string parameters. Returns the full `$_GET` array when called without arguments.

- **Parameters:**
  - `string|null $key`: Specific key to retrieve, or `null` for all query params.
  - `mixed $default`: Default value if key is not found.

- **Returns:** `mixed`

- **Example:**
  ```php
  $allQuery = $this->request->query();           // all GET params
  $page     = $this->request->query('page', 1);  // single param
  ```

#### `put(?string $key = null, mixed $default = null): mixed`

Retrieves PUT data from `php://input`. Supports JSON and URL-encoded payloads based on Content-Type. Pass `null` as key to retrieve all parsed data.

- **Parameters:**
  - `string|null $key`: The key to retrieve, or `null` for all data.
  - `mixed $default`: Default value if key is not found.

- **Returns:** `mixed`

- **Example:**
  ```php
  $name = $this->request->put('name');
  ```

#### `patch(?string $key = null, mixed $default = null): mixed`

Retrieves PATCH data from `php://input`. Behaves identically to `put()`.

- **Parameters:**
  - `string|null $key`: The key to retrieve, or `null` for all data.
  - `mixed $default`: Default value if key is not found.

- **Returns:** `mixed`

#### `input(string $key, mixed $default = null): mixed`

Get a value from the merged request input (GET + POST + parsed JSON body).

- **Parameters:**
  - `string $key`: The key to retrieve.
  - `mixed $default`: Default value if key is not found.

- **Returns:** `mixed`

- **Example:**
  ```php
  $name = $this->request->input('name');
  ```

#### `all(): array`

Get all input data (GET + POST + parsed JSON body merged).

- **Returns:** `array`

- **Example:**
  ```php
  $data = $this->request->all();
  ```

#### `only(array $keys): array`

Get only the specified keys from input.

- **Parameters:**
  - `array $keys`: The keys to retrieve.

- **Returns:** `array`

- **Example:**
  ```php
  $credentials = $this->request->only(['email', 'password']);
  ```

#### `except(array $keys): array`

Get all input except the specified keys.

- **Parameters:**
  - `array $keys`: Keys to exclude.

- **Returns:** `array`

- **Example:**
  ```php
  $data = $this->request->except(['_token']);
  ```

#### `has(string $key): bool`

Check if the input has a given key.

- **Parameters:**
  - `string $key`: The key to check.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->has('email')) { ... }
  ```

#### `filled(string $key): bool`

Check if the input has a given key with a non-empty value.

- **Parameters:**
  - `string $key`: The key to check.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->filled('name')) { ... }
  ```

#### `json(): mixed`

Parse the raw JSON request body. Returns `null` if the Content-Type is not JSON or the body is empty.

- **Returns:** `mixed`

- **Example:**
  ```php
  $data = $this->request->json();
  ```

#### `bearerToken(): ?string`

Get the bearer token from the `Authorization` header.

- **Returns:** `string|null`

- **Example:**
  ```php
  $token = $this->request->bearerToken();
  ```

---

### Request Info Methods

#### `method(): string`

Get the HTTP method of the current request (uppercase).

- **Returns:** `string`

- **Example:**
  ```php
  if ($this->request->method() === 'PATCH') { ... }
  ```

#### `path(): string`

Get the request URI path (without query string).

- **Returns:** `string`

- **Example:**
  ```php
  $currentPath = $this->request->path();
  ```

#### `uri(): string`

Get the full URI (path + query string).

- **Returns:** `string`

- **Example:**
  ```php
  $uri = $this->request->uri();
  ```

#### `fullUrl(): string`

Get the full URL of the current request (scheme + host + URI).

- **Returns:** `string`

- **Example:**
  ```php
  $url = $this->request->fullUrl();
  ```

#### `scheme(): string`

Get the URL scheme (`http` or `https`).

- **Returns:** `string`

- **Example:**
  ```php
  if ($this->request->scheme() === 'https') { ... }
  ```

#### `host(): string`

Get the host from the current request.

- **Returns:** `string`

- **Example:**
  ```php
  $host = $this->request->host();
  ```

#### `port(): int`

Get the port from the current request.

- **Returns:** `int`

- **Example:**
  ```php
  $port = $this->request->port();
  ```

#### `origin(): string`

Get the request origin (scheme + host).

- **Returns:** `string`

- **Example:**
  ```php
  $origin = $this->request->origin();  // "https://example.com"
  ```

#### `ip(): ?string`

Get the client IP address. Checks `X-Forwarded-For`, `HTTP_CLIENT_IP`, and `REMOTE_ADDR`.

- **Returns:** `string|null`

- **Example:**
  ```php
  $clientIp = $this->request->ip();
  ```

#### `userAgent(): ?string`

Get the user agent string.

- **Returns:** `string|null`

- **Example:**
  ```php
  $ua = $this->request->userAgent();
  ```

#### `referer(): ?string`

Get the referer URL.

- **Returns:** `string|null`

- **Example:**
  ```php
  $referrer = $this->request->referer();
  ```

#### `header(string $name): ?string`

Get a single header value.

- **Parameters:**
  - `string $name`: The header name (case-insensitive).

- **Returns:** `string|null`

- **Example:**
  ```php
  $contentType = $this->request->header('Content-Type');
  ```

#### `headers(): array`

Get all headers as an associative array.

- **Returns:** `array<string, string>`

- **Example:**
  ```php
  $allHeaders = $this->request->headers();
  ```

---

### Boolean Flag Methods

#### `isPost(): bool`

Checks if the current request is a `POST` request.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->isPost()) {
    // Handle POST request
  }
  ```

#### `isGet(): bool`

Checks if the current request is a `GET` request.

- **Returns:** `bool`

#### `isPut(): bool`

Checks if the current request is a `PUT` request.

- **Returns:** `bool`

#### `isDelete(): bool`

Checks if the current request is a `DELETE` request.

- **Returns:** `bool`

#### `isPatch(): bool`

Checks if the current request is a `PATCH` request.

- **Returns:** `bool`

#### `isAjax(): bool`

Checks if the current request is an AJAX request.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->isAjax()) {
    // Handle AJAX request
  }
  ```

#### `isSecure(): bool`

Checks if the current request is made over HTTPS.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->isSecure()) {
    // Secure connection logic
  }
  ```

#### `isMobile(): bool`

Checks if the current request is made from a mobile device.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->isMobile()) {
    // Mobile-specific logic
  }
  ```

#### `isRobot(): bool`

Checks if the current request is from a web crawler or bot.

- **Returns:** `bool`

- **Example:**
  ```php
  if ($this->request->isRobot()) {
    // Bot-specific handling
  }
  ```

### Error Handling

This class directly interacts with the PHP superglobals and is generally robust against missing keys, as it always falls back on a default value (`null` by default) when a key is not found.
