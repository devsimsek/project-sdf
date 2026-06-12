<?php

/**
 * smskSoft SDF Schema Builder
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Schema
 * @file        Schema.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/schema
 * @since       Version 2.3
 * @filesource
 */

namespace SDF\Schema;

use PDO;
use SDF\Spark;

/**
 * Static facade for schema management.
 *
 * Provides a fluent API for creating and modifying database tables:
 *   Schema::create('users', function (Blueprint $table) { ... });
 *   Schema::table('users', function (Blueprint $table) { ... });
 *   Schema::drop('users');
 */
class Schema
{
    /**
     * Create a new table.
     *
     * Builds and executes a CREATE TABLE statement from the Blueprint definition.
     *
     * @param string   $table    Table name.
     * @param callable $callback Receives a Blueprint instance.
     * @return void
     */
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = self::buildCreateSql($blueprint);
        Spark::pdo()->exec($sql);
    }

    /**
     * Modify an existing table.
     *
     * Builds and executes ALTER TABLE statements for each column / constraint.
     *
     * @param string   $table    Table name.
     * @param callable $callback Receives a Blueprint instance.
     * @return void
     */
    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $pdo = Spark::pdo();
        $driver = self::driver($pdo);

        foreach ($blueprint->getColumns() as $column) {
            $colSql = self::compileColumnSql($column, $driver);
            if ($driver === 'sqlite') {
                $pdo->exec("ALTER TABLE \"{$table}\" ADD COLUMN {$colSql}");
            } else {
                $pdo->exec("ALTER TABLE `{$table}` ADD {$colSql}");
            }
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $def = $fk->getDefinition();
            $fkSql = self::compileForeignKeySql($def, $driver);
            if ($driver === 'sqlite') {
                $pdo->exec("ALTER TABLE \"{$table}\" ADD {$fkSql}");
            } else {
                $pdo->exec("ALTER TABLE `{$table}` ADD {$fkSql}");
            }
        }
    }

    /**
     * Drop a table.
     *
     * @param string $table Table name.
     * @return void
     */
    public static function drop(string $table): void
    {
        $pdo = Spark::pdo();
        $driver = self::driver($pdo);
        if ($driver === 'sqlite') {
            $pdo->exec("DROP TABLE IF EXISTS \"{$table}\"");
        } else {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    /**
     * Drop a table only if it exists (alias of drop).
     *
     * @param string $table Table name.
     * @return void
     */
    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $table Table name.
     * @return bool
     */
    public static function hasTable(string $table): bool
    {
        $pdo = Spark::pdo();
        $driver = self::driver($pdo);

        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if a column exists in the given table.
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return bool
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $pdo = Spark::pdo();
        $driver = self::driver($pdo);

        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("PRAGMA table_info(\"{$table}\")");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                if (($col['name'] ?? '') === $column) {
                    return true;
                }
            }
            return false;
        }

        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Build a complete CREATE TABLE SQL statement.
     *
     * @param Blueprint $blueprint
     * @return string
     */
    private static function buildCreateSql(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $pdo = Spark::pdo();
        $driver = self::driver($pdo);
        $quote = $driver === 'sqlite' ? '"' : '`';
        $parts = [];

        foreach ($blueprint->getColumns() as $column) {
            $parts[] = self::compileColumnSql($column, $driver, $quote);
        }

        $primaryKeys = $blueprint->getPrimaryKeys();
        if (!empty($primaryKeys)) {
            $autoIncrementCols = array_map(
                fn ($c) => $c['name'],
                array_filter($blueprint->getColumns(), fn ($c) => ($c['extra'] ?? null) === 'auto_increment')
            );
            $pkCols = array_map(fn ($c) => $quote . $c . $quote, array_diff($primaryKeys, $autoIncrementCols));
            if (!empty($pkCols)) {
                $parts[] = 'PRIMARY KEY (' . implode(', ', $pkCols) . ')';
            }
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $parts[] = self::compileForeignKeySql($fk->getDefinition(), $driver, $quote);
        }

        $columnsSql = implode(', ', $parts);
        return "CREATE TABLE {$quote}{$table}{$quote} ({$columnsSql})";
    }

    /**
     * Compile a single column definition SQL fragment.
     *
     * @param array  $column Column definition array.
     * @param string $driver PDO driver name.
     * @param string $quote  Identifier quote character.
     * @return string
     */
    private static function compileColumnSql(array $column, string $driver, string $quote = '`'): string
    {
        $name = $quote . $column['name'] . $quote;
        $type = self::compileType($column, $driver);
        $sql = "{$name} {$type}";

        if (!empty($column['extra']) && $column['extra'] === 'auto_increment') {
            if ($driver === 'sqlite') {
                $sql .= ' PRIMARY KEY AUTOINCREMENT';
            } else {
                $sql .= ' AUTO_INCREMENT';
            }
        }

        if (!empty($column['nullable'])) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        if (array_key_exists('default', $column) && $column['default'] !== null) {
            $default = is_string($column['default'])
                ? "'{$column['default']}'"
                : $column['default'];
            $sql .= ' DEFAULT ' . $default;
        }

        if (!empty($column['unique'])) {
            if ($driver === 'sqlite') {
                $sql .= ' UNIQUE';
            } else {
                $sql .= ' UNIQUE';
            }
        }

        return $sql;
    }

    /**
     * Map a Blueprint column type to the appropriate SQL type string.
     *
     * @param array  $column Column definition.
     * @param string $driver PDO driver name.
     * @return string
     */
    private static function compileType(array $column, string $driver): string
    {
        return match ($column['type']) {
            'id' => $driver === 'sqlite' ? 'INTEGER' : 'BIGINT',
            'string' => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'integer' => 'INT(' . ($column['length'] ?? 11) . ')',
            'bigInteger' => 'BIGINT',
            'boolean' => $driver === 'sqlite' ? 'INTEGER' : 'TINYINT(1)',
            'text' => 'TEXT',
            'float' => 'FLOAT(' . ($column['length'] ?? 8) . ', ' . ($column['scale'] ?? 2) . ')',
            'decimal' => 'DECIMAL(' . ($column['length'] ?? 8) . ', ' . ($column['scale'] ?? 2) . ')',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            default => 'TEXT',
        };
    }

    /**
     * Compile a FOREIGN KEY constraint SQL fragment.
     *
     * @param array  $def    Foreign key definition.
     * @param string $driver PDO driver name.
     * @param string $quote  Identifier quote character.
     * @return string
     */
    private static function compileForeignKeySql(array $def, string $driver, string $quote = '`'): string
    {
        $localCol = $quote . $def['column'] . $quote;
        $refTable = $quote . $def['on'] . $quote;
        $refCol = $quote . $def['references'] . $quote;

        return "FOREIGN KEY ({$localCol}) REFERENCES {$refTable} ({$refCol})"
            . " ON DELETE {$def['onDelete']}"
            . " ON UPDATE {$def['onUpdate']}";
    }

    /**
     * Get the PDO driver name (mysql, sqlite, etc.).
     *
     * @param PDO $pdo
     * @return string
     */
    private static function driver(PDO $pdo): string
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
