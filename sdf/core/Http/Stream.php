<?php

declare(strict_types=1);

namespace SDF\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * smskSoft SDF PSR-7 Stream
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Http
 * @file        Stream.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/http.md
 * @since       Version 2.0
 * @filesource
 */
class Stream implements StreamInterface
{
    /** @var resource|null Underlying stream resource. */
    private $stream = null;

    /** @var bool Whether the stream is seekable. */
    private bool $seekable;

    /** @var bool Whether the stream is readable. */
    private bool $readable;

    /** @var bool Whether the stream is writable. */
    private bool $writable;

    /** @var array<string, mixed>|null Cached stream metadata. */
    private ?array $metadata = null;

    /** @var int|null Cached size in bytes. */
    private ?int $size = null;

    /**
     * @param resource|string $body  Stream resource or string content.
     * @param string          $mode  Stream open mode (used when $body is a resource).
     *
     * @throws RuntimeException When unable to open php://temp.
     * @throws \InvalidArgumentException When body is not a string or resource.
     */
    public function __construct(mixed $body = '', string $mode = 'r')
    {
        if (is_string($body)) {
            $resource = fopen('php://temp', 'r+');
            if ($resource === false) {
                throw new RuntimeException('Unable to open php://temp');
            }
            fwrite($resource, $body);
            rewind($resource);
            $this->stream = $resource;
        } elseif (is_resource($body)) {
            $this->stream = $body;
        } else {
            throw new \InvalidArgumentException(
                'Stream body must be a string or resource',
            );
        }

        $this->setMetadata();
    }

    /**
     * Read entire stream contents.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->stream) {
            return '';
        }

        try {
            $this->rewind();
            return stream_get_contents($this->stream);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Close the underlying stream and detach.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->stream) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * Detach the underlying resource from the stream.
     *
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;
        $this->size = null;
        $this->metadata = null;
        return $resource;
    }

    /**
     * Get the stream size in bytes.
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!$this->stream) {
            return null;
        }

        $stats = fstat($this->stream);
        $this->size = $stats['size'] ?? null;
        return $this->size;
    }

    /**
     * Get the current position of the stream pointer.
     *
     * @return int
     *
     * @throws RuntimeException When the stream is detached or unable to determine position.
     */
    public function tell(): int
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached');
        }

        $pos = ftell($this->stream);
        if ($pos === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $pos;
    }

    /**
     * Check if the stream pointer is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * Check if the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @param int $offset Offset value.
     * @param int $whence Seek mode (SEEK_SET, SEEK_CUR, SEEK_END).
     *
     * @return void
     *
     * @throws RuntimeException When the stream is detached or not seekable, or seek fails.
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek stream');
        }
    }

    /**
     * Rewind to the beginning of the stream.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Check if the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string to write.
     *
     * @return int Number of bytes written.
     *
     * @throws RuntimeException When the stream is detached or not writable, or write fails.
     */
    public function write(string $string): int
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $this->size = null;
        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $written;
    }

    /**
     * Check if the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Number of bytes to read.
     *
     * @return string
     *
     * @throws RuntimeException When the stream is detached or not readable, or read fails.
     */
    public function read(int $length): string
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $data;
    }

    /**
     * Get the remaining contents of the stream.
     *
     * @return string
     *
     * @throws RuntimeException When the stream is detached or unable to read contents.
     */
    public function getContents(): string
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * Get stream metadata.
     *
     * @param string|null $key Optional metadata key.
     *
     * @return mixed
     */
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->metadata === null) {
            return $key === null ? null : null;
        }

        return $key === null
            ? $this->metadata
            : ($this->metadata[$key] ?? null);
    }

    /**
     * Cache stream metadata and derive seekable/readable/writable flags.
     *
     * @return void
     */
    private function setMetadata(): void
    {
        $this->metadata = $this->stream
            ? stream_get_meta_data($this->stream)
            : null;

        if ($this->metadata) {
            $mode = $this->metadata['mode'] ?? '';
            $this->seekable = $this->metadata['seekable'] ?? false;
            $this->readable = str_contains($mode, 'r') || str_contains($mode, '+');
            $this->writable = str_contains($mode, 'w')
                || str_contains($mode, 'a')
                || str_contains($mode, 'x')
                || str_contains($mode, 'c')
                || str_contains($mode, '+');
        }
    }
}
