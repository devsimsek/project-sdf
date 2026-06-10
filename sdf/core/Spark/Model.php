<?php

/**
 * smskSoft SDF Spark Model
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Spark
 * @file        Model.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/spark.md
 * @since       Version 2.1
 * @filesource
 */

namespace SDF\Spark;

use SDF\Spark;
use SDF\QueryBuilder;

abstract class Model
{
    /** @var string Custom table name. */
    protected static string $table = '';

    /** @var string Primary key column name. */
    protected static string $primaryKey = 'id';

    /** @var array Current model attribute values. */
    protected array $attributes = [];

    /** @var array Original attribute values loaded from the database. */
    protected array $original = [];

    /**
       * @param array $data       Initial attribute data.
       * @param bool  $isOriginal Whether the data originates from the database.
       */
    public function __construct(array $data = [], bool $isOriginal = false)
    {
        $this->fill($data);
        if ($isOriginal) {
            $this->original = $data;
        }
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $data
     * @return self
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Magic getter for attributes.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Magic setter for attributes.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Check if the model or a specific attribute has changed since it was
     * last retrieved or saved.
     *
     * @param string|null $name Optional attribute name to check.
     * @return bool
     */
    public function isDirty(?string $name = null): bool
    {
        if ($name) {
            return ($this->attributes[$name] ?? null) !== ($this->original[$name] ?? null);
        }

        return $this->attributes !== $this->original;
    }

    /**
     * Get the attributes that have been modified since the last sync.
     *
     * @return array
     */
    public function getDirty(): array
    {
        return array_filter($this->attributes, function ($value, $key) {
            return !array_key_exists($key, $this->original) || $value !== $this->original[$key];
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Persist the model. Inserts a new row or updates the existing one
     * depending on whether the primary key is already set.
     *
     * @return bool
     * @throws \Exception
     */
    public function save(): bool
    {
        $pk = static::$primaryKey;

        if (isset($this->original[$pk])) {
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true;
            }

            unset($dirty[$pk]);

            $success = static::query()
                ->where($pk, $this->original[$pk])
                ->update($dirty);

            if ($success) {
                $this->original = $this->attributes;
            }
            return $success;
        }

        $success = static::query()->insert($this->attributes);
        if ($success) {
            if (!isset($this->attributes[$pk])) {
                $this->attributes[$pk] = Spark::pdo()->lastInsertId();
            }
            $this->original = $this->attributes;
        }
        return $success;
    }

    /**
     * Delete the model row from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $pk = static::$primaryKey;
        if (!isset($this->attributes[$pk])) {
            return false;
        }

        return static::query()->where($pk, $this->attributes[$pk])->delete();
    }

    /**
     * Get the table name associated with this model. Defaults to the
     * lowercase pluralised class name when $table is not overridden.
     *
     * @return string
     */
    public static function getTable(): string
    {
        if (empty(static::$table)) {
            $class = explode('\\', static::class);
            return strtolower(end($class)) . 's';
        }
        return static::$table;
    }

    /**
     * Start a new query builder instance scoped to this model's table.
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return Spark::table(static::getTable())->as(static::class);
    }

    /**
     * Find a record by its primary key value.
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        $data = static::query()->where(static::$primaryKey, $id)->first();
        if ($data instanceof static) {
            return $data;
        }

        if (is_array($data)) {
            $class = static::class;
            return new $class($data, true);
        }

        return null;
    }

    /**
     * Start a query with a WHERE clause.
     *
     * @param string $column
     * @param mixed  $operator
     * @param mixed  $value
     * @return QueryBuilder
     */
    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return self::query()->where($column, $operator, $value);
    }

    /**
     * Retrieve all records for this model.
     *
     * @return static[]
     */
    public static function all(): array
    {
        $class = static::class;
        return array_map(
            fn ($data) => $data instanceof static ? $data : new $class((array) $data, true),
            self::query()->get()
        );
    }

    /**
     * Create and persist a new model instance.
     *
     * @param array $data
     * @return static|null
     */
    public static function create(array $data): ?static
    {
        $class = static::class;
        $instance = new $class($data);
        return $instance->save() ? $instance : null;
    }

    /**
     * Delete records matching the given criteria.
     *
     * @param array $where Associative array of column => value pairs.
     * @return bool
     */
    public static function destroy(array $where): bool
    {
        $query = self::query();
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->delete();
    }

    // todo: write documentation & tests
    public function toArray(): array
    {
        $data = $this->attributes;

        $infer = function ($v) {
            if (is_null($v) || is_bool($v) || is_int($v) || is_float($v) || is_array($v) || is_object($v)) {
                return $v;
            }

            if (is_string($v)) {
                $s = trim($v);

                // null-ish
                if ($s === '') {
                    return $v;
                }

                // boolean-ish
                $lower = strtolower($s);
                if (in_array($lower, ['true','false','1','0','yes','no'], true)) {
                    return in_array($lower, ['true','1','yes'], true);
                }

                // integer
                if (ctype_digit($s) || preg_match('/^[+-]?\d+$/', $s)) {
                    // protect large ints: intval is fine for typical ranges
                    return (int)$s;
                }

                // float (decimal or exponent)
                if (is_numeric($s) && (str_contains($s, '.') || stripos($s, 'e') !== false)) {
                    return (float)$s;
                }

                // json array/object -> decode
                if (($json = json_decode($s, true)) !== null && json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }

            return $v;
        };

        foreach ($data as $k => $v) {
            $data[$k] = $infer($v);
        }

        return $data;
    }


    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
