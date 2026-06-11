<?php

/**
 * smskSoft SDF Schema Blueprint
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Schema
 * @file        Blueprint.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/schema
 * @since       Version 2.3
 * @filesource
 */

namespace SDF\Schema;

/**
 * Fluent column definition class.
 *
 * Provides a DSL for defining table columns and constraints:
 *   $table->id();
 *   $table->string('name');
 *   $table->timestamps();
 */
class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $foreignKeys = [];
    private array $primaryKeys = [];

    /**
     * @param string $table The target table name.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Add an auto-incrementing bigint primary key column.
     *
     * @param string $name Column name (default 'id').
     * @return $this
     */
    public function id(string $name = 'id'): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'id',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => 'auto_increment',
        ];
        $this->primaryKeys[] = $name;
        return $this;
    }

    /**
     * Add a VARCHAR column.
     *
     * @param string $name   Column name.
     * @param int    $length Maximum length (default 255).
     * @return $this
     */
    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'string',
            'length'   => $length,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add an INT column.
     *
     * @param string $name   Column name.
     * @param int    $length Display width (default 11).
     * @return $this
     */
    public function integer(string $name, int $length = 11): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'integer',
            'length'   => $length,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a BIGINT column.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function bigInteger(string $name): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'bigInteger',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a boolean / TINYINT column.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function boolean(string $name): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'boolean',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a TEXT column.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function text(string $name): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'text',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a FLOAT column.
     *
     * @param string $name      Column name.
     * @param int    $precision Total digit count (default 8).
     * @param int    $scale     Digits after decimal point (default 2).
     * @return $this
     */
    public function float(string $name, int $precision = 8, int $scale = 2): static
    {
        $this->columns[] = [
            'name'      => $name,
            'type'      => 'float',
            'length'    => $precision,
            'nullable'  => false,
            'default'   => null,
            'unique'    => false,
            'extra'     => null,
            'scale'     => $scale,
        ];
        return $this;
    }

    /**
     * Add a DECIMAL column.
     *
     * @param string $name      Column name.
     * @param int    $precision Total digit count (default 8).
     * @param int    $scale     Digits after decimal point (default 2).
     * @return $this
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): static
    {
        $this->columns[] = [
            'name'      => $name,
            'type'      => 'decimal',
            'length'    => $precision,
            'nullable'  => false,
            'default'   => null,
            'unique'    => false,
            'extra'     => null,
            'scale'     => $scale,
        ];
        return $this;
    }

    /**
     * Add a DATE column.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function date(string $name): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'date',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a DATETIME column.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function dateTime(string $name): static
    {
        $this->columns[] = [
            'name'     => $name,
            'type'     => 'dateTime',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add created_at and updated_at DATETIME columns.
     *
     * @return $this
     */
    public function timestamps(): static
    {
        $this->columns[] = [
            'name'     => 'created_at',
            'type'     => 'dateTime',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        $this->columns[] = [
            'name'     => 'updated_at',
            'type'     => 'dateTime',
            'length'   => null,
            'nullable' => false,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Add a nullable deleted_at DATETIME column for soft deletes.
     *
     * @return $this
     */
    public function softDeletes(): static
    {
        $this->columns[] = [
            'name'     => 'deleted_at',
            'type'     => 'dateTime',
            'length'   => null,
            'nullable' => true,
            'default'  => null,
            'unique'   => false,
            'extra'    => null,
        ];
        return $this;
    }

    /**
     * Make the last added column nullable.
     *
     * @return $this
     */
    public function nullable(): static
    {
        if (!empty($this->columns)) {
            $idx = array_key_last($this->columns);
            $this->columns[$idx]['nullable'] = true;
        }
        return $this;
    }

    /**
     * Set a default value on the last added column.
     *
     * @param mixed $value Default value.
     * @return $this
     */
    public function default(mixed $value): static
    {
        if (!empty($this->columns)) {
            $idx = array_key_last($this->columns);
            $this->columns[$idx]['default'] = $value;
        }
        return $this;
    }

    /**
     * Add a UNIQUE constraint on the last added column.
     *
     * @return $this
     */
    public function unique(): static
    {
        if (!empty($this->columns)) {
            $idx = array_key_last($this->columns);
            $this->columns[$idx]['unique'] = true;
        }
        return $this;
    }

    /**
     * Set the primary key for the table.
     *
     * @param string|array $columns Column name or array of column names.
     * @return $this
     */
    public function primary(string|array $columns): static
    {
        $this->primaryKeys = array_merge($this->primaryKeys, (array) $columns);
        return $this;
    }

    /**
     * Start a foreign key definition for the given column.
     *
     * Returns a ForeignKeyDefinition instance for chaining references(), on(), etc.
     *
     * @param string $column The local column name.
     * @return ForeignKeyDefinition
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $definition = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $definition;
        return $definition;
    }

    /**
     * Return all column definitions.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Return the target table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Return all foreign key definitions.
     *
     * @return ForeignKeyDefinition[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Return the primary key column names.
     *
     * @return array
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }
}
