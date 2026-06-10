<?php

declare(strict_types=1);

namespace SDF\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * smskSoft SDF PSR-7 UploadedFile
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Http
 * @file        UploadedFile.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/http.md
 * @since       Version 2.0
 * @filesource
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var StreamInterface|null Stream for the uploaded file. */
    private ?StreamInterface $stream = null;

    /** @var string|null Path to the temporary uploaded file. */
    private ?string $file = null;

    /** @var int|null File size in bytes. */
    private ?int $size = null;

    /** @var int Upload error code (UPLOAD_ERR_*). */
    private int $error;

    /** @var string|null Original client filename. */
    private ?string $clientFilename = null;

    /** @var string|null Client media type. */
    private ?string $clientMediaType = null;

    /** @var bool Whether the file has been moved. */
    private bool $moved = false;

    /**
     * @param string|StreamInterface $file            Temporary file path or stream.
     * @param int                    $error           Upload error code (UPLOAD_ERR_*).
     * @param string|null            $clientFilename  Original client filename.
     * @param string|null            $clientMediaType Client media type.
     * @param int|null               $size            File size in bytes.
     */
    public function __construct(
        string|StreamInterface $file,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
        ?int $size = null,
    ) {
        $this->error = $error;

        if ($error !== UPLOAD_ERR_OK) {
            return;
        }

        if ($file instanceof StreamInterface) {
            $this->stream = $file;
        } else {
            $this->file = $file;
        }

        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        $this->size = $size;
    }

    /**
     * Get a stream representing the uploaded file.
     *
     * @return StreamInterface
     *
     * @throws RuntimeException When the file has already been moved or is unavailable.
     */
    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved');
        }

        if ($this->stream !== null) {
            return $this->stream;
        }

        if ($this->file === null) {
            throw new RuntimeException('No file or stream available');
        }

        $resource = fopen($this->file, 'r');
        if ($resource === false) {
            throw new RuntimeException("Unable to open uploaded file: {$this->file}");
        }

        $this->stream = new Stream($resource);
        return $this->stream;
    }

    /**
     * Move the uploaded file to a target location.
     *
     * @param string $targetPath Destination path.
     *
     * @return void
     *
     * @throws RuntimeException When the file has already been moved, no temporary file is available,
     *                          the target directory does not exist, or the move fails.
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved');
        }

        if ($this->file === null) {
            throw new RuntimeException('No temporary file path available');
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            throw new RuntimeException("Target directory does not exist: $dir");
        }

        if (rename($this->file, $targetPath) === false) {
            if (!copy($this->file, $targetPath)) {
                throw new RuntimeException("Unable to move file to: $targetPath");
            }
            unlink($this->file);
        }

        $this->moved = true;
    }

    /**
     * Get the file size.
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Get the upload error code.
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get the original client filename.
     *
     * @return string|null
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Get the client media type.
     *
     * @return string|null
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
