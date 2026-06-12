<?php

declare(strict_types=1);

namespace SDF\Storage\Drivers;

use SDF\Storage\Contracts\StorageDriver;

class LocalDriver implements StorageDriver
{
    private string $root;
    private string $urlPrefix;

    public function __construct(string $root, string $urlPrefix = '')
    {
        $this->root = rtrim($root, '/');
        $this->urlPrefix = rtrim($urlPrefix, '/');
    }

    private function resolve(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }

    public function exists(string $path): bool
    {
        return file_exists($this->resolve($path));
    }

    public function get(string $path): ?string
    {
        $full = $this->resolve($path);
        if (!is_file($full)) {
            return null;
        }
        $contents = file_get_contents($full);
        return $contents === false ? null : $contents;
    }

    public function put(string $path, string $contents): bool
    {
        $full = $this->resolve($path);
        $dir = dirname($full);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($full, $contents, LOCK_EX) !== false;
    }

    public function stream(string $path, $resource): bool
    {
        $full = $this->resolve($path);
        $dir = dirname($full);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = fopen($full, 'wb');
        if ($dest === false) {
            return false;
        }
        $written = stream_copy_to_stream($resource, $dest);
        fclose($dest);
        return $written !== false;
    }

    public function delete(string $path): bool
    {
        $full = $this->resolve($path);
        if (!is_file($full)) {
            return false;
        }
        return unlink($full);
    }

    public function url(string $path): string
    {
        if ($this->urlPrefix === '') {
            return $path;
        }
        return $this->urlPrefix . '/' . ltrim($path, '/');
    }

    public function size(string $path): int
    {
        $full = $this->resolve($path);
        if (!is_file($full)) {
            return 0;
        }
        return filesize($full);
    }

    public function mimeType(string $path): string
    {
        $full = $this->resolve($path);
        if (!is_file($full)) {
            return 'application/octet-stream';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $full);
        finfo_close($finfo);
        return $mime ?: 'application/octet-stream';
    }

    public function files(?string $directory = null): array
    {
        $dir = $directory !== null ? $this->resolve($directory) : $this->root;
        if (!is_dir($dir)) {
            return [];
        }
        $items = scandir($dir);
        if ($items === false) {
            return [];
        }
        $result = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $result[] = ($directory !== null ? $directory . '/' : '') . $item;
            }
        }
        return $result;
    }

    public function allFiles(?string $directory = null): array
    {
        $dir = $directory !== null ? $this->resolve($directory) : $this->root;
        if (!is_dir($dir)) {
            return [];
        }
        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $result[] = ($directory !== null ? $directory . '/' : '') . $iterator->getSubPathname();
            }
        }
        sort($result);
        return $result;
    }

    public function directories(?string $directory = null): array
    {
        $dir = $directory !== null ? $this->resolve($directory) : $this->root;
        if (!is_dir($dir)) {
            return [];
        }
        $items = scandir($dir);
        if ($items === false) {
            return [];
        }
        $result = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $result[] = ($directory !== null ? $directory . '/' : '') . $item;
            }
        }
        return $result;
    }

    public function copy(string $from, string $to): bool
    {
        $fullFrom = $this->resolve($from);
        $fullTo = $this->resolve($to);
        $dir = dirname($fullTo);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return copy($fullFrom, $fullTo);
    }

    public function move(string $from, string $to): bool
    {
        $fullFrom = $this->resolve($from);
        $fullTo = $this->resolve($to);
        $dir = dirname($fullTo);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return rename($fullFrom, $fullTo);
    }

    public function makeDirectory(string $path): bool
    {
        $full = $this->resolve($path);
        if (is_dir($full)) {
            return true;
        }
        return mkdir($full, 0755, true);
    }

    public function deleteDirectory(string $path): bool
    {
        $full = $this->resolve($path);
        if (!is_dir($full)) {
            return false;
        }
        $this->rmdirRecursive($full);
        return true;
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
