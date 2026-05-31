<?php

namespace SDF\Spark;

use PDO;
use PDOException;
use SDF\Logger;
use SDF\Level;

/**
 * Connection pool for Spark ORM.
 * Manages named PDO connections with persistent connection support.
 */
class Pool
{
    /** @var array<string, PDO> Named connections */
    private static array $connections = [];

    /** @var array<string, array> Connection configs for lazy reconnect */
    private static array $configs = [];

    /** @var string|null Default connection name */
    private static ?string $default = null;

    /**
     * Register a named connection.
     */
    public static function add(
        string $name,
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
        bool $persistent = false,
    ): void {
        if ($persistent) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        self::$configs[$name] = [$dsn, $username, $password, $options];
        if (self::$default === null) {
            self::$default = $name;
        }
    }

    /**
     * Get a connection by name (lazy-initialised).
     */
    public static function get(?string $name = null): PDO
    {
        $name ??= self::$default;
        if ($name === null) {
            throw new \RuntimeException("Spark Pool: no connection registered.");
        }

        if (!isset(self::$connections[$name])) {
            if (!isset(self::$configs[$name])) {
                throw new \RuntimeException("Spark Pool: unknown connection '$name'.");
            }
            [$dsn, $username, $password, $options] = self::$configs[$name];
            try {
                self::$connections[$name] = new PDO($dsn, $username, $password, $options);
                self::$connections[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connections[$name]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                Logger::log(Level::FATAL, "Spark Pool Error: " . $e->getMessage(), ['connection' => $name, 'exception' => $e]);
                throw $e;
            }
        }
        return self::$connections[$name];
    }

    /**
     * Check if a named connection exists (config or live).
     */
    public static function has(string $name): bool
    {
        return isset(self::$configs[$name]) || isset(self::$connections[$name]);
    }

    /**
     * Disconnect and remove a named connection.
     */
    public static function remove(string $name): void
    {
        unset(self::$connections[$name], self::$configs[$name]);
        if (self::$default === $name) {
            self::$default = null;
        }
    }

    /**
     * Disconnect all and clear the pool.
     */
    public static function reset(): void
    {
        self::$connections = [];
        self::$configs = [];
        self::$default = null;
    }

    /**
     * Get all active connection names.
     * @return string[]
     */
    public static function active(): array
    {
        return array_keys(self::$connections);
    }

    /**
     * Set the default connection name.
     */
    public static function setDefault(string $name): void
    {
        self::$default = $name;
    }

    /**
     * Get the default connection name.
     */
    public static function getDefault(): ?string
    {
        return self::$default;
    }
}
