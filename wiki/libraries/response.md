# SDF Response Documentation

## Overview

The `Response` class is part of the **SDF Core** library, providing functionality for building and sending HTTP
responses, including JSON, plain text, and HTML responses. It allows for easy management of headers and HTTP status
codes.

## Class: `Response`

This class extends the `Core` class and handles HTTP response functionalities such as setting HTTP status codes, adding
headers, and sending responses in different formats.

Also this library is loaded by the `Controller` class and can be accessed via the `$this->response` property.

### Properties

- **`$headers`** (array): Stores headers to be sent with the response.
- **`$httpCode`** (int): HTTP status code for the response (e.g., 200 for OK, 400 for Bad Request).

### Methods

#### `setHttpCode(int $httpCode): void`

Sets the HTTP status code for the response.

- **Parameters:**
  - `int $httpCode`: The HTTP status code to set (e.g., 200, 404, 500).

- **Returns:** `void`

#### `addHeader(string $header): void`

Adds a header to the response.

- **Parameters:**
  - `string $header`: The header string (e.g., `Content-Type: application/json`).

- **Returns:** `void`

#### `sendHeaders(): void` (protected)

Builds and sends all the headers stored in the `$headers` array, along with the HTTP status code. It will terminate
execution with a 500 status code if headers have already been sent or if the HTTP code is not set.

- **Returns:** `void`

#### `json(mixed $object, int $httpCode = null): void`

Sends a response as JSON.

- **Parameters:**
  - `mixed $object`: The object to be JSON-encoded and sent.
  - `int|null $httpCode`: Optional HTTP status code (defaults to 200 if not provided).

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->json(["message" => "Success"], 200);
  ```

#### `text(string $message, int $httpCode = null): void`

Sends a response as plain text.

- **Parameters:**
  - `string $message`: The plain text message to send.
  - `int|null $httpCode`: Optional HTTP status code (uses the previously set code if null).

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->text("Plain text message", 200);
  ```

#### `html(string $html, int $httpCode = null): void`

Sends a response as HTML.

- **Parameters:**
  - `string $html`: The HTML content to send.
  - `int|null $httpCode`: Optional HTTP status code.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->html("<h1>Welcome</h1>", 200);
  ```

### Error Handling

- If headers have already been sent, a 500 HTTP status code is set and the program exits with an error message.
- If the HTTP status code is not set before sending headers, a 500 status code is returned and execution stops.
