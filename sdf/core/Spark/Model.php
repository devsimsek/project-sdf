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
        return static::query()->where(static::$primaryKey, $id)->first();
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
        $qb = static::query();
        if (func_num_args() === 2) {
            return $qb->where($column, $operator);
        }
        return $qb->where($column, $operator, $value);
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

    /**
     * Define a one-to-one relationship.
     *
     * @param string      $related    Related model class name.
     * @param string|null $foreignKey Foreign key on the related table.
     * @param string|null $localKey   Local key on this model's table.
     * @return static|null
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): ?object
    {
        $instance = new $related();
        $localKey = $localKey ?? static::$primaryKey;
        $foreignKey = $foreignKey ?? self::classToSnake(static::class) . '_id';

        return $instance::where($foreignKey, $this->attributes[$localKey] ?? null)->first();
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string      $related    Related model class name.
     * @param string|null $foreignKey Foreign key on the related table.
     * @param string|null $localKey   Local key on this model's table.
     * @return object[]
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $instance = new $related();
        $localKey = $localKey ?? static::$primaryKey;
        $foreignKey = $foreignKey ?? self::classToSnake(static::class) . '_id';

        return $instance::where($foreignKey, $this->attributes[$localKey] ?? null)->get();
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param string      $related   Parent model class name.
     * @param string|null $foreignKey Foreign key on this model's table.
     * @param string|null $ownerKey  Owner key on the parent table.
     * @return object|null
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): ?object
    {
        $instance = new $related();
        $ownerKey = $ownerKey ?? $instance->getPrimaryKey();
        $foreignKey = $foreignKey ?? $this->guessForeignKey($related);

        return $instance::where($ownerKey, $this->attributes[$foreignKey] ?? null)->first();
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string      $related         Related model class name.
     * @param string|null $pivotTable      Pivot table name.
     * @param string|null $foreignPivotKey Foreign key on the pivot table for this model.
     * @param string|null $relatedPivotKey Foreign key on the pivot table for the related model.
     * @return static[]
     */
    public function belongsToMany(string $related, ?string $pivotTable = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): array
    {
        $instance = new $related();
        $pivotTable = $pivotTable ?? $this->guessPivotTable(static::class, $related);
        $foreignPivotKey = $foreignPivotKey ?? $this->guessForeignKey(static::class);
        $relatedPivotKey = $relatedPivotKey ?? $this->guessForeignKey($related);
        $localValue = $this->attributes[static::$primaryKey] ?? null;

        if ($localValue === null) {
            return [];
        }

        $relatedTable = $instance::getTable();
        $relatedPk = $instance->getPrimaryKey();

        $query = Spark::table($pivotTable)
            ->select("{$relatedTable}.*")
            ->as($related)
            ->join($relatedTable, "{$relatedTable}.{$relatedPk}", '=', "{$pivotTable}.{$relatedPivotKey}")
            ->where("{$pivotTable}.{$foreignPivotKey}", $localValue);

        return $query->get();
    }

    /**
     * Get the primary key column name.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Convert a PascalCase class name to snake_case table name.
     *
     * @param string $class
     * @return string
     */
    private static function classToSnake(string $class): string
    {
        $parts = explode('\\', $class);
        $name = end($parts);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /**
     * Guess the foreign key column name for a given class.
     *
     * @param string $class
     * @return string
     */
    private function guessForeignKey(string $class): string
    {
        return self::classToSnake($class) . '_id';
    }

    /**
     * Guess the pivot table name from two class names.
     *
     * @param string $class1
     * @param string $class2
     * @return string
     */
    private function guessPivotTable(string $class1, string $class2): string
    {
        $tables = [self::classToSnake($class1), self::classToSnake($class2)];
        sort($tables);
        return implode('_', $tables);
    }

    public function toArray(): array
    {
        $data = $this->attributes;

        $infer = function ($v) {
            if (is_null($v) || is_bool($v) || is_int($v) || is_float($v) || is_array($v) || is_object($v)) {
                return $v;
            }

            if (is_string($v)) {
                $s = trim($v);

                if ($s === '') {
                    return $v;
                }

                if (ctype_digit($s) || preg_match('/^[+-]?\d+$/', $s)) {
                    return (int) $s;
                }

                if (is_numeric($s)) {
                    return (float) $s;
                }

                $lower = strtolower($s);
                if (in_array($lower, ['true','false','yes','no'], true)) {
                    return in_array($lower, ['true','yes'], true);
                }

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
