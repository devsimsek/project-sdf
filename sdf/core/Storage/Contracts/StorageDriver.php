<?php

declare(strict_types=1);

namespace SDF\Storage\Contracts;

interface StorageDriver
{
    public function exists(string $path): bool;
    public function get(string $path): ?string;
    public function put(string $path, string $contents): bool;
    public function stream(string $path, $resource): bool;
    public function delete(string $path): bool;
    public function url(string $path): string;
    public function size(string $path): int;
    public function mimeType(string $path): string;
    public function files(?string $directory = null): array;
    public function allFiles(?string $directory = null): array;
    public function directories(?string $directory = null): array;
    public function copy(string $from, string $to): bool;
    public function move(string $from, string $to): bool;
    public function makeDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;
}
