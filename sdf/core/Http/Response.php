<?php

declare(strict_types=1);

namespace SDF\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * smskSoft SDF PSR-7 Response
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Http
 * @file        Response.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/http.md
 * @since       Version 2.0
 * @filesource
 */
class Response implements ResponseInterface
{
    /** @var string Protocol version (e.g. '1.1', '2.0'). */
    private string $protocolVersion = '1.1';

    /** @var array<string, string[]> Headers mapped by original name. */
    private array $headers = [];

    /** @var array<string, string> Lowercased name to original header name. */
    private array $headerNames = [];

    /** @var StreamInterface Message body. */
    private StreamInterface $body;

    /** @var int HTTP status code. */
    private int $statusCode;

    /** @var string HTTP reason phrase. */
    private string $reasonPhrase = '';

    /** @var array<int, string> Default reason phrases per HTTP status code. */
    private const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        426 => 'Upgrade Required',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * @param int                        $status  HTTP status code.
     * @param array<string, string|string[]> $headers Response headers.
     * @param StreamInterface|string|null $body    Response body.
     * @param string                     $version HTTP protocol version.
     * @param string|null                $reason  Reason phrase (auto-resolved when null).
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
        ?string $reason = null,
    ) {
        $this->statusCode = $status;
        $this->protocolVersion = $version;
        $this->reasonPhrase = $reason ?? (self::PHRASES[$status] ?? '');

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
        $values = $this->getHeader($name);
        return implode(', ', $values);
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
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and reason phrase.
     *
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return static
     */
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::PHRASES[$code] ?? '');
        return $new;
    }

    /**
     * Get the HTTP reason phrase.
     *
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
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
        foreach ($this->headers as $existing => $v) {
            if (strtolower($existing) === $lower && $existing !== $name) {
                unset($this->headers[$existing]);
            }
        }
        $this->headerNames[$lower] = $name;
        $this->headers[$name] = $values;
    }
}
