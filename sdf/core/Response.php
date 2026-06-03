<?php

namespace SDF;

/**
 * smskSoft SDF Response
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Response.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#response
 * @since       Version 1.0
 * @filesource
 */
class Response
{
    use CoreUtilities;


    // Array to store raw header strings (for direct header() calls)
    protected array $headers = [];

    // Named headers (e.g., ['Content-Type' => 'application/json'])
    protected array $namedHeaders = [];

    // HTTP status code (e.g., 200 for OK, 400 for Bad Request)
    protected int $httpCode;

    // Response body content
    protected string $content = '';

    /**
     * Set the HTTP status code for the response.
     *
     * @param int $httpCode
     * @return void
     */
    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    /**
     * Get the current HTTP status code.
     *
     * @return int|null
     */
    public function statusCode(): ?int
    {
        return $this->httpCode ?? null;
    }

    /**
     * Add a raw header string to the response.
     *
     * @param string $header
     * @return void
     */
    public function addHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Set a named header (replaces any existing value for that name).
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setHeader(string $name, string $value): self
    {
        $this->namedHeaders[$name] = $value;
        return $this;
    }

    /**
     * Get a named header value.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->namedHeaders[$name] ?? null;
    }

    /**
     * Check if a named header has been set.
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->namedHeaders[$name]);
    }

    /**
     * Remove a named header.
     *
     * @param string $name
     * @return self
     */
    public function removeHeader(string $name): self
    {
        unset($this->namedHeaders[$name]);
        return $this;
    }

    /**
     * Clear all headers (both raw and named).
     *
     * @return self
     */
    public function clearHeaders(): self
    {
        $this->headers = [];
        $this->namedHeaders = [];
        return $this;
    }

    /**
     * Build and send all headers.
     *
     * @return void
     */
    protected function sendHeaders(): void
    {
        if (empty($this->httpCode)) {
            // Log and convert to exception so callers/middleware can handle the error
            Logger::log(Level::ERROR, 'HTTP code not set. Cannot send headers.', [
                'file' => __FILE__,
                'line' => __LINE__,
                'headers' => $this->headers,
            ]);
            throw new HttpResponseException('HTTP code not set. Cannot send headers.', 500);
        }

        if ($this->headersAlreadySent()) {
            Logger::log(Level::ERROR, 'Headers have already been sent. Cannot send additional headers.', [
                'file' => __FILE__,
                'line' => __LINE__,
                'headers' => $this->headers,
            ]);
            throw new HeadersSendException('Headers have already been sent. Cannot send additional headers.', 500);
        }

        // Send named headers first (as key: value)
        foreach ($this->namedHeaders as $name => $value) {
            header("$name: $value");
        }

        // Send raw header strings
        foreach ($this->headers as $header) {
            header($header);
        }

        // Set the HTTP response code
        http_response_code($this->httpCode);
    }

    /**
     * Wrapper for headers_sent() so tests can override behaviour.
     *
     * @return bool
     */
    protected function headersAlreadySent(): bool
    {
        return headers_sent();
    }

    /**
     * Send the response as JSON.
     * todo: create a ticket response methods do not halt execution
     *
     * @param mixed $object
     * @param int|null $httpCode
     * @return void
     */
    public function json(mixed $object, ?int $httpCode = null): void
    {
        $this->setHeader('Content-Type', 'application/json');

        if ($httpCode) {
            $this->setHttpCode($httpCode);
        } else {
            $this->setHttpCode(200);
        }

        $this->sendHeaders();
        echo json_encode($object);
    }

    public function text(string $message, ?int $httpCode = null): void
    {
        $this->setHeader('Content-Type', 'text/plain');

        if ($httpCode) {
            $this->setHttpCode($httpCode);
        }

        $this->sendHeaders();
        echo $message;
    }

    public function html(string $html, ?int $httpCode = null): void
    {
        $this->setHeader('Content-Type', 'text/html');

        if ($httpCode) {
            $this->setHttpCode($httpCode);
        }

        $this->sendHeaders();
        echo $html;
    }

    /**
     * Send a redirect response.
     *
     * @param string $url
     * @param int $statusCode
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setHeader('Location', $url);
        $this->setHttpCode($statusCode);
        $this->sendHeaders();
    }

    /**
     * Send a 204 No Content response.
     *
     * @return void
     */
    public function noContent(): void
    {
        $this->setHttpCode(204);
        $this->sendHeaders();
    }

    /**
     * Send an XML response.
     *
     * @param string $xml
     * @param int|null $httpCode
     * @return void
     */
    public function xml(string $xml, ?int $httpCode = null): void
    {
        $this->setHeader('Content-Type', 'application/xml');

        if ($httpCode) {
            $this->setHttpCode($httpCode);
        }

        $this->sendHeaders();
        echo $xml;
    }

    /**
     * Send a file as a download response.
     *
     * @param string $file Path to the file.
     * @param string|null $name Optional download filename.
     * @return void
     */
    public function download(string $file, ?string $name = null): void
    {
        if (!file_exists($file)) {
            throw new HttpResponseException("File not found: $file", 404);
        }

        $name = $name ?? basename($file);
        $size = filesize($file);
        $mime = mime_content_type($file) ?: 'application/octet-stream';

        $this->setHeader('Content-Type', $mime);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"');
        $this->setHeader('Content-Length', (string) $size);
        $this->setHttpCode(200);
        $this->sendHeaders();
        readfile($file);
    }

    /**
     * Set the response body content.
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response body content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Clear the response body and reset state.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->content = '';
        $this->httpCode = 0;
        $this->clearHeaders();
        return $this;
    }

    /**
     * Set Cache-Control max-age in minutes.
     *
     * @param int $minutes
     * @return self
     */
    public function cache(int $minutes): self
    {
        $this->setHeader('Cache-Control', 'max-age=' . ($minutes * 60));
        return $this;
    }

    /**
     * Disable caching for this response.
     *
     * @return self
     */
    public function noCache(): self
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', '0');
        return $this;
    }

    /**
     * Set the Content-Type header.
     *
     * @param string $mime
     * @return self
     */
    public function type(string $mime): self
    {
        $this->setHeader('Content-Type', $mime);
        return $this;
    }

    /**
     * Send the current body content with headers.
     *
     * @return void
     */
    public function send(): void
    {
        if (empty($this->httpCode)) {
            $this->setHttpCode(200);
        }

        if (empty($this->namedHeaders['Content-Type'])) {
            $this->setHeader('Content-Type', 'text/html');
        }

        $this->sendHeaders();
        echo $this->content;
    }
}
