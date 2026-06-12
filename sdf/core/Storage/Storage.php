<?php

declare(strict_types=1);

namespace SDF\Storage;

use SDF\Core;
use SDF\Storage\Contracts\StorageDriver;
use SDF\Storage\Drivers\LocalDriver;
use SDF\Storage\Drivers\S3Driver;

class Storage
{
    private static ?self $instance = null;
    private StorageDriver $driver;
    private string $defaultDisk;

    public function __construct(StorageDriver $driver, string $defaultDisk = 'local')
    {
        $this->driver = $driver;
        $this->defaultDisk = $defaultDisk;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = self::fromConfig();
        }
        return self::$instance;
    }

    public static function fromConfig(): self
    {
        $config = Core::coreGetConfig('storage') ?: [];

        $defaultDisk = $config['default'] ?? 'local';
        $disks = $config['disks'] ?? [];
        $diskConfig = $disks[$defaultDisk] ?? [];

        $driver = self::resolveDriver($defaultDisk, $diskConfig);

        return new self($driver, $defaultDisk);
    }

    private static function resolveDriver(string $disk, array $config): StorageDriver
    {
        return match ($disk) {
            's3' => new S3Driver($config),
            default => new LocalDriver(
                $config['root'] ?? sys_get_temp_dir() . '/sdf_storage',
                $config['url'] ?? '',
            ),
        };
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = self::instance();
        return $instance->driver->{$method}(...$args);
    }

    public function disk(?string $name = null): StorageDriver
    {
        if ($name === null || $name === $this->defaultDisk) {
            return $this->driver;
        }
        $config = Core::coreGetConfig('storage') ?: [];
        $disks = $config['disks'] ?? [];
        $diskConfig = $disks[$name] ?? [];
        return self::resolveDriver($name, $diskConfig);
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
