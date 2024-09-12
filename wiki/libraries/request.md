# SDF Request Documentation

## Overview

The `Request` class is part of the **SDF Core** library and provides methods to interact with various PHP superglobal arrays, such as `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_SESSION`, `$_COOKIE`, and `$_FILES`. It also offers methods to check request types, such as `POST`, `GET`, AJAX, secure connections, mobile device access, and bot requests.

## Class: `Request`

This class extends the `Core` class and handles HTTP request functionalities, such as retrieving request data and identifying the type of request being made.

This library is loaded by the `Controller` class and can be accessed via the `$this->request` property.

### Methods

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

#### `post(string $key, mixed $default = null): mixed`

Retrieves a value from the `$_POST` array.

- **Parameters:**
  - `string $key`: The key of the value in the `$_POST` array.
  - `mixed $default`: The default value to return if the key does not exist (default is `null`).

- **Returns:** `mixed`

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

- **Example:**
  ```php
  if ($this->request->isGet()) {
    // Handle GET request
  }
  ```

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
