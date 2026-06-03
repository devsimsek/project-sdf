# SDF Response Documentation

## Overview

The `Response` class is part of the **SDF Core** library, providing functionality for building and sending HTTP
responses, including JSON, plain text, HTML, XML, file downloads, and redirects. It allows for management of both
raw and named headers, HTTP status codes, and response body content.

## Class: `Response`

This class uses the `CoreUtilities` trait and handles HTTP response functionalities.

Loaded by the `Controller` class and accessed via `$this->response`.

### Properties

- **`$headers`** (array): Stores raw header strings to be sent with the response.
- **`$namedHeaders`** (array): Stores named headers (e.g., `['Content-Type' => 'application/json']`).
- **`$httpCode`** (int): HTTP status code for the response (e.g., 200 for OK, 400 for Bad Request).
- **`$content`** (string): Response body content.

### Status Code Methods

#### `setHttpCode(int $httpCode): void`

Sets the HTTP status code for the response.

- **Parameters:**
  - `int $httpCode`: The HTTP status code to set (e.g., 200, 404, 500).

- **Returns:** `void`

#### `statusCode(): ?int`

Get the current HTTP status code.

- **Returns:** `int|null`

- **Example:**
  ```php
  $code = $this->response->statusCode();
  ```

### Header Methods

#### `addHeader(string $header): void`

Adds a raw header string to the response.

- **Parameters:**
  - `string $header`: The header string (e.g., `Content-Type: application/json`).

- **Returns:** `void`

#### `setHeader(string $name, string $value): self`

Set a named header. Replaces any existing value for that name.

- **Parameters:**
  - `string $name`: The header name (e.g., `Content-Type`).
  - `string $value`: The header value.

- **Returns:** `self` (fluent)

- **Example:**
  ```php
  $this->response->setHeader('X-Custom', 'value');
  ```

#### `getHeader(string $name): ?string`

Get a named header value.

- **Parameters:**
  - `string $name`: The header name.

- **Returns:** `string|null`

#### `hasHeader(string $name): bool`

Check if a named header has been set.

- **Parameters:**
  - `string $name`: The header name.

- **Returns:** `bool`

#### `removeHeader(string $name): self`

Remove a named header.

- **Parameters:**
  - `string $name`: The header name.

- **Returns:** `self` (fluent)

#### `clearHeaders(): self`

Clear all headers (both raw and named).

- **Returns:** `self` (fluent)

#### `sendHeaders(): void` (protected)

Builds and sends all headers (named first, then raw strings), along with the HTTP status code. Throws a 500 error
if headers have already been sent or if the HTTP code is not set.

- **Returns:** `void`

### Body / Content Methods

#### `setContent(string $content): self`

Set the response body content.

- **Parameters:**
  - `string $content`: The body content.

- **Returns:** `self` (fluent)

#### `getContent(): string`

Get the response body content.

- **Returns:** `string`

#### `clear(): self`

Clear the response body and reset headers and status code.

- **Returns:** `self` (fluent)

#### `send(): void`

Send the current body content with headers. Defaults to 200 and `text/html` if not set.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response
      ->setContent('<h1>Hello</h1>')
      ->setHeader('X-Custom', 'value')
      ->send();
  ```

### Output Methods

#### `json(mixed $object, ?int $httpCode = null): void`

Sends a response as JSON (`Content-Type: application/json`).

- **Parameters:**
  - `mixed $object`: The object to be JSON-encoded and sent.
  - `int|null $httpCode`: Optional HTTP status code (defaults to 200).

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->json(["message" => "Success"], 200);
  ```

#### `text(string $message, ?int $httpCode = null): void`

Sends a response as plain text (`Content-Type: text/plain`).

- **Parameters:**
  - `string $message`: The plain text message to send.
  - `int|null $httpCode`: Optional HTTP status code.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->text("Plain text message", 200);
  ```

#### `html(string $html, ?int $httpCode = null): void`

Sends a response as HTML (`Content-Type: text/html`).

- **Parameters:**
  - `string $html`: The HTML content to send.
  - `int|null $httpCode`: Optional HTTP status code.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->html("<h1>Welcome</h1>", 200);
  ```

#### `xml(string $xml, ?int $httpCode = null): void`

Sends an XML response (`Content-Type: application/xml`).

- **Parameters:**
  - `string $xml`: The XML content to send.
  - `int|null $httpCode`: Optional HTTP status code.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->xml($xmlString, 200);
  ```

#### `redirect(string $url, int $statusCode = 302): void`

Send a redirect response.

- **Parameters:**
  - `string $url`: The URL to redirect to.
  - `int $statusCode`: HTTP status code (default 302).

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->redirect('/login');
  $this->response->redirect('/dashboard', 301);
  ```

#### `noContent(): void`

Send a 204 No Content response.

- **Returns:** `void`

- **Example:**
  ```php
  $this->response->noContent();
  ```

#### `download(string $file, ?string $name = null): void`

Send a file as a download response. Automatically sets `Content-Type`, `Content-Disposition`, and `Content-Length`.

- **Parameters:**
  - `string $file`: Path to the file.
  - `string|null $name`: Optional download filename (defaults to basename of the file).

- **Returns:** `void`

- **Throws:** `HttpResponseException` if the file is not found.

- **Example:**
  ```php
  $this->response->download('/path/to/report.pdf', 'report.pdf');
  ```

### Cache Control Methods

#### `cache(int $minutes): self`

Set `Cache-Control: max-age` in minutes.

- **Parameters:**
  - `int $minutes`: Cache lifetime in minutes.

- **Returns:** `self` (fluent)

- **Example:**
  ```php
  $this->response->cache(60)->json($data);
  ```

#### `noCache(): self`

Disable caching. Sets `Cache-Control: no-store, no-cache, must-revalidate`, `Pragma: no-cache`, and `Expires: 0`.

- **Returns:** `self` (fluent)

- **Example:**
  ```php
  $this->response->noCache()->json($data);
  ```

### Utility Methods

#### `type(string $mime): self`

Set the `Content-Type` header.

- **Parameters:**
  - `string $mime`: The MIME type (e.g., `application/json`).

- **Returns:** `self` (fluent)

- **Example:**
  ```php
  $this->response->type('text/csv')->send();
  ```

### Error Handling

- If headers have already been sent, a `HeadersSendException` (500) is thrown.
- If the HTTP status code is not set before sending headers, an `HttpResponseException` (500) is thrown.
- `download()` throws `HttpResponseException` (404) if the file does not exist.
