<?php

/**
 * smskSoft SDF Environment
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Env.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/env
 * @since       Version 2.2
 * @filesource
 */

namespace SDF;

/**
 * Lightweight .env loader.
 *
 * Loads KEY=VALUE pairs from a .env file and sets them via putenv() + $_ENV.
 * Supports quoted values, inline comments (#), and blank lines.
 *
 * Usage:
 *   Env::load(__DIR__ . '/../.env');
 *   $dbHost = Env::get('DB_HOST', 'localhost');
 */
class Env
{
    /** @var array<string, string> Loaded variables. */
    private static array $vars = [];

    /**
     * Load a .env file.
     *
     * @param string $path Absolute path to .env file.
     * @return void
     */
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            $value = self::stripComments($value);
            $value = self::unquote($value);
            $value = self::resolvePlaceholders($value);

            if ($key !== '') {
                self::set($key, $value);
            }
        }
    }

    /**
     * Get an environment variable.
     *
     * @param string     $key     Variable name.
     * @param mixed|null $default Fallback when not set.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }
        return $default;
    }

    /**
     * Set a variable at runtime.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        self::$vars[$key] = $value;
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }

    /**
     * Check if a variable exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$vars) || getenv($key) !== false;
    }

    /**
     * Strip trailing inline comments (#), respecting single/double quotes.
     */
    private static function stripComments(string $value): string
    {
        $inSingle = false;
        $inDouble = false;
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            if ($ch === "'" && !$inDouble) {
                $inSingle = !$inSingle;
            } elseif ($ch === '"' && !$inSingle) {
                $inDouble = !$inDouble;
            } elseif ($ch === '#' && !$inSingle && !$inDouble) {
                return trim(substr($value, 0, $i));
            }
        }
        return $value;
    }

    /**
     * Remove matching surrounding quotes.
     */
    private static function unquote(string $value): string
    {
        $len = strlen($value);
        if ($len < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$len - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Resolve ${VAR_NAME} placeholders.
     */
    private static function resolvePlaceholders(string $value, int $depth = 0): string
    {
        if ($depth > 10) {
            return $value;
        }
        return preg_replace_callback('/\$\{([^}]+)\}/', function ($m) use ($depth) {
            return self::resolvePlaceholders(self::get($m[1], ''), $depth + 1);
        }, $value);
    }

    /**
     * Reset loaded variables (for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$vars = [];
    }
}
