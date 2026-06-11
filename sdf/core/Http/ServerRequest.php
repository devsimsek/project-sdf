<?php

declare(strict_types=1);

namespace SDF\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * smskSoft SDF PSR-7 ServerRequest
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Http
 * @file        ServerRequest.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/http.md
 * @since       Version 2.0
 * @filesource
 */
class ServerRequest implements ServerRequestInterface
{
    /** @var string HTTP protocol version. */
    private string $protocolVersion = '1.1';

    /** @var array<string, string[]> Headers mapped by original name. */
    private array $headers = [];

    /** @var array<string, string> Lowercased name to original header name. */
    private array $headerNames = [];

    /** @var StreamInterface Request body. */
    private StreamInterface $body;

    /** @var string HTTP method (uppercased). */
    private string $method = 'GET';

    /** @var UriInterface Request URI. */
    private UriInterface $uri;

    /** @var string Custom request target (empty to auto-resolve). */
    private string $requestTarget = '';

    /** @var array Server parameters ($_SERVER). */
    private array $serverParams = [];

    /** @var array Cookie parameters ($_COOKIE). */
    private array $cookieParams = [];

    /** @var array Query parameters ($_GET). */
    private array $queryParams = [];

    /** @var array<string, \Psr\Http\Message\UploadedFileInterface> Uploaded files. */
    private array $uploadedFiles = [];

    /** @var array|object|null Parsed body. */
    private null|array|object $parsedBody = null;

    /** @var array<string, mixed> Custom attributes. */
    private array $attributes = [];

    /**
     * @param string                        $method        HTTP method.
     * @param string|UriInterface           $uri           Request URI.
     * @param array<string, string|string[]> $headers      Request headers.
     * @param StreamInterface|string|null   $body          Request body.
     * @param string                        $version       HTTP protocol version.
     * @param array                         $serverParams  $_SERVER-like parameters.
     */
    public function __construct(
        string $method = 'GET',
        string|UriInterface $uri = '',
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
        array $serverParams = [],
    ) {
        $this->method = strtoupper($method);
        $this->protocolVersion = $version;
        $this->serverParams = $serverParams;

        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);

        if ($body === null) {
            $body = new Stream('', 'r');
        } elseif (is_string($body)) {
            $body = new Stream($body, 'r');
        }

        $this->body = $body;

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    /**
     * Create a ServerRequest from PHP superglobals.
     *
     * @return self
     *
     * @throws RuntimeException When unable to open php://input.
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = new Uri(
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://'
            . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . ($_SERVER['REQUEST_URI'] ?? '/'),
        );

        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        }

        $inputStream = fopen('php://input', 'r');
        if ($inputStream === false) {
            throw new RuntimeException('Unable to open php://input');
        }
        $body = new Stream($inputStream);

        $parsedBody = null;
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json') || str_contains($contentType, 'application/x-json')) {
            $rawBody = $body->__toString();
            if ($rawBody !== '') {
                $parsedBody = json_decode($rawBody, false);
                $body->rewind();
            }
        } elseif ($method === 'POST') {
            $parsedBody = $_POST;
        }

        $uploadedFiles = self::parseUploadedFiles($_FILES);

        $serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $version = $serverProtocol === 'HTTP/1.1' ? '1.1' : '1.0';

        $request = new self(
            $method,
            $uri,
            $headers,
            $body,
            $version,
            $_SERVER,
        );

        $request->queryParams = $_GET;
        $request->cookieParams = $_COOKIE;
        $request->parsedBody = $parsedBody;
        $request->uploadedFiles = $uploadedFiles;

        return $request;
    }

    /**
     * Get the HTTP protocol version.
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified protocol version.
     *
     * @param string $version
     *
     * @return static
     */
    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    /**
     * Get all message headers.
     *
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Check if a header exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * Get a header by name (array of values).
     *
     * @param string $name
     *
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        $lower = strtolower($name);

        if (!isset($this->headerNames[$lower])) {
            return [];
        }

        $actual = $this->headerNames[$lower];
        return $this->headers[$actual];
    }

    /**
     * Get a comma-separated header line.
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the specified header.
     *
     * @param string          $name
     * @param string|string[] $value
     *
     * @return static
     */
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->setHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance with an additional header value.
     *
     * @param string          $name
     * @param string|string[] $value
     *
     * @return static
     */
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $values = $new->getHeader($name);
        $values = array_merge($values, (array) $value);
        $new->setHeader($name, $values);
        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name
     *
     * @return static
     */
    public function withoutHeader(string $name): static
    {
        $lower = strtolower($name);

        if (!isset($this->headerNames[$lower])) {
            return clone $this;
        }

        $new = clone $this;
        $actual = $new->headerNames[$lower];
        unset($new->headers[$actual], $new->headerNames[$lower]);
        return $new;
    }

    /**
     * Get the message body.
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified body.
     *
     * @param StreamInterface $body
     *
     * @return static
     */
    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    /**
     * Get the request target (path + query).
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * Return an instance with the specified request target.
     *
     * @param string $requestTarget
     *
     * @return static
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * Get the HTTP method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the specified HTTP method.
     *
     * @param string $method
     *
     * @return static
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    /**
     * Get the request URI.
     *
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Return an instance with the specified URI, optionally preserving the Host header.
     *
     * @param UriInterface $uri
     * @param bool         $preserveHost
     *
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $host = $uri->getHost();
            if ($host !== '') {
                $port = $uri->getPort();
                $host .= $port !== null ? ':' . $port : '';
                $new->setHeader('Host', $host);
            }
        }

        return $new;
    }

    /**
     * Get the server parameters.
     *
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Get the cookie parameters.
     *
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Return an instance with the specified cookie parameters.
     *
     * @param array<string, string> $cookies
     *
     * @return static
     */
    public function withCookieParams(array $cookies): static
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * Get the query parameters.
     *
     * @return array<string, string>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query parameters.
     *
     * @param array $query
     *
     * @return static
     */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * Get the uploaded files.
     *
     * @return array<string, \Psr\Http\Message\UploadedFileInterface>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Return an instance with the specified uploaded files.
     *
     * @param array $uploadedFiles
     *
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * Get the parsed body.
     *
     * @return array|object|null
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified parsed body.
     *
     * @param array|object|null $data
     *
     * @return static
     */
    public function withParsedBody($data): static
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * Get all custom attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a single custom attribute.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->attributes)
            ? $this->attributes[$name]
            : $default;
    }

    /**
     * Return an instance with the specified attribute.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function withAttribute(string $name, $value): static
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    /**
     * Return an instance without the specified attribute.
     *
     * @param string $name
     *
     * @return static
     */
    public function withoutAttribute(string $name): static
    {
        if (!array_key_exists($name, $this->attributes)) {
            return clone $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    /**
     * Internal helper to register a header maintaining casing.
     *
     * @param string          $name
     * @param string|string[] $values
     *
     * @return void
     */
    private function setHeader(string $name, array|string $values): void
    {
        $values = is_array($values) ? $values : [$values];
        $lower = strtolower($name);
        $this->headerNames[$lower] = $name;
        $this->headers[$name] = $values;
    }

    /**
     * Parse $_FILES into a structured array of UploadedFile instances.
     *
     * @param array $files $_FILES superglobal.
     *
     * @return array<string, \Psr\Http\Message\UploadedFileInterface>
     */
    private static function parseUploadedFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $spec) {
            if (is_array($spec['error'] ?? null)) {
                $result[$key] = self::parseNestedFiles($spec);
            } elseif (isset($spec['tmp_name'])) {
                $result[$key] = new UploadedFile(
                    $spec['tmp_name'],
                    $spec['error'] ?? UPLOAD_ERR_NO_FILE,
                    $spec['name'] ?? null,
                    $spec['type'] ?? null,
                    $spec['size'] ?? null,
                );
            }
        }

        return $result;
    }

    /**
     * Recursively parse a nested $_FILES array (multi-file uploads).
     *
     * @param array $spec A single entry from $_FILES.
     *
     * @return array
     */
    private static function parseNestedFiles(array $spec): array
    {
        $result = [];
        $files = $spec['name'] ?? [];

        foreach ($files as $index => $name) {
            $error = $spec['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if (is_array($name)) {
                $result[$index] = self::parseNestedFiles([
                    'name' => $spec['name'][$index],
                    'type' => $spec['type'][$index] ?? [],
                    'tmp_name' => $spec['tmp_name'][$index] ?? [],
                    'error' => $spec['error'][$index] ?? [],
                    'size' => $spec['size'][$index] ?? [],
                ]);
            } else {
                $result[$index] = new UploadedFile(
                    $spec['tmp_name'][$index] ?? '',
                    $error,
                    $name,
                    $spec['type'][$index] ?? null,
                    $spec['size'][$index] ?? null,
                );
            }
        }

        return $result;
    }
}
