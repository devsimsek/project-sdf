<?php

namespace SDF;

use Exception;
use PDO;
use PDOException;

/**
 * Spark ORM
 * A modern QueryBuilder and Active Record implementation.
 */
class Spark
{
    /**
     * @var PDO|null $pdo Global PDO connection instance.
     */
    private static ?PDO $pdo = null;

    /**
     * Establish database connection.
     *
     * @param string $dsn      Data Source Name.
     * @param string $username Database username.
     * @param string $password Database password.
     * @param array  $options  PDO connection options.
     * @return void
     */
    public static function connect(string $dsn, string $username = '', string $password = '', array $options = []): void
    {
        try {
            self::$pdo = new PDO($dsn, $username, $password, $options);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Spark DB Error: " . $e->getMessage());
        }
    }

    /**
     * Get active PDO instance.
     *
     * @return PDO
     * @throws Exception If connection is not established.
     */
    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new Exception("Spark ORM: Database not connected.");
        }
        return self::$pdo;
    }

    /**
     * Start a new query on the given table.
     *
     * @param string $table Table name.
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }
}

/**
 * Fluent Query Builder for Spark ORM.
 */
class QueryBuilder
{
    /** @var string $table Target table name. */
    protected string $table;

    /** @var array $wheres List of WHERE clauses. */
    protected array $wheres = [];

    /** @var array $bindings Parameter bindings for the query. */
    protected array $bindings = [];

    /** @var string|null $orderBy ORDER BY clause. */
    protected ?string $orderBy = null;

    /** @var string|null $limit LIMIT clause. */
    protected ?string $limit = null;

    /** @var string|null $modelClass Optional class name to hydrate results into. */
    protected ?string $modelClass = null;

    /**
     * Initialize builder with table.
     *
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Add a basic where clause.
     *
     * @param string $column   Column name.
     * @param mixed  $operator Comparison operator or value.
     * @param mixed  $value    Value to compare against if operator is provided.
     * @return self
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column    Column name.
     * @param string $direction Sort direction (ASC/DESC).
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = " ORDER BY $column $direction";
        return $this;
    }

    /**
     * Add a LIMIT clause.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = " LIMIT $limit";
        return $this;
    }

    /**
     * Set the model class to hydrate results into.
     *
     * @param string $class
     * @return self
     */
    public function as(string $class): self
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * Get the first record matching the query.
     *
     * @return array|null
     */
    public function first(): mixed
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $sql .= $this->orderBy ?? "";
        $sql .= " LIMIT 1";

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();

        if (!$result) return null;

        if ($this->modelClass) {
            return new $this->modelClass($result, true);
        }

        return $result;
    }

    /**
     * Execute SELECT query and return results.
     *
     * @return array Array of records.
     */
    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $sql .= $this->orderBy ?? "";
        $sql .= $this->limit ?? "";

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        $results = $stmt->fetchAll();

        if ($this->modelClass) {
            return array_map(fn($data) => new $this->modelClass($data, true), $results);
        }

        return $results;
    }

    /**
     * Count the number of records matching the query.
     *
     * @return int
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Calculate average of a column.
     *
     * @param string $column
     * @return float
     */
    public function avg(string $column): float
    {
        $sql = "SELECT AVG($column) FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Insert a new record into the table.
     *
     * @param array $data Key-value pairs of column names and values.
     * @return bool True on success, false on failure.
     */
    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = Spark::pdo()->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    /**
     * Update existing records.
     *
     * @param array $data
     * @return bool
     */
    public function update(array $data): bool
    {
        $fields = [];
        $values = [];
        foreach ($data as $column => $value) {
            $fields[] = "$column = ?";
            $values[] = $value;
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields);
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }
        $stmt = Spark::pdo()->prepare($sql);
        return $stmt->execute(array_merge($values, $this->bindings));
    }

    /**
     * Delete records.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }
        $stmt = Spark::pdo()->prepare($sql);
        return $stmt->execute($this->bindings);
    }
}

namespace SDF\Spark;

use SDF\Spark;
use SDF\QueryBuilder;

/**
 * Base Active Record Model for Spark ORM.
 */
abstract class Model
{
    /** @var string $table Custom table name. */
    protected static string $table = '';

    /** @var string $primaryKey Primary key of the table. */
    protected static string $primaryKey = 'id';

    /** @var array $attributes Model attributes/data. */
    protected array $attributes = [];

    /** @var array $original Original attributes to track changes. */
    protected array $original = [];

    /**
     * Initialize model with optional data.
     *
     * @param array $data
     * @param bool  $isOriginal Whether the data is from the database.
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
     * Check if the model or a specific attribute has changed.
     */
    public function isDirty(?string $name = null): bool
    {
        if ($name) {
            return ($this->attributes[$name] ?? null) !== ($this->original[$name] ?? null);
        }

        return $this->attributes !== $this->original;
    }

    /**
     * Get the attributes that have changed.
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Save the current model instance to the database.
     * Handles both INSERT and UPDATE.
     *
     * @return bool
     */
    public function save(): bool
    {
        $pk = static::$primaryKey;

        if (isset($this->original[$pk])) {
            // Update
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true;
            }

            // Don't update the primary key
            unset($dirty[$pk]);

            $success = static::query()
                ->where($pk, $this->original[$pk])
                ->update($dirty);

            if ($success) {
                $this->original = $this->attributes;
            }
            return $success;
        }

        // Insert
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
     * Delete the current model instance.
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
     * Get the associated table name.
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
     * Start a new query for this model.
     */
    public static function query(): QueryBuilder
    {
        return Spark::table(static::getTable())->as(static::class);
    }

    /**
     * Find a record by primary key.
     *
     * @param mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        $data = static::query()->where(static::$primaryKey, $id)->first();
        return $data ? new static($data, true) : null;
    }

    /**
     * Start a query with a where clause.
     */
    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return self::query()->where($column, $operator, $value);
    }

    /**
     * Retrieve all records as model instances.
     *
     * @return static[]
     */
    public static function all(): array
    {
        $results = self::query()->get();
        return array_map(fn($data) => new static($data, true), $results);
    }

    /**
     * Create and save a new model instance.
     *
     * @param array $data
     * @return static|null
     */
    public static function create(array $data): ?static
    {
        $instance = new static($data);
        return $instance->save() ? $instance : null;
    }

    /**
     * Destroy records by criteria (Static).
     *
     * @param array $where
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
}

/**
 * Base Migration class for Spark ORM.
 */
abstract class Migration
{
    /**
     * Run the migrations.
     *
     * @param \PDO $pdo
     * @return void
     */
    abstract public function up(\PDO $pdo): void;

    /**
     * Reverse the migrations.
     *
     * @param \PDO $pdo
     * @return void
     */
    abstract public function down(\PDO $pdo): void;
}

/**
 * Base Seeder class for Spark ORM.
 */
abstract class Seeder
{
    /**
     * Run the database seeds.
     *
     * @param \PDO $pdo
     * @return void
     */
    abstract public function run(\PDO $pdo): void;
}
