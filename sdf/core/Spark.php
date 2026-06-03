<?php

namespace SDF;

use Exception;
use PDO;
use PDOException;
use SDF\Spark\Pool;

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
     * Establish database connection (lazy — actual connect on first query).
     *
     * @param string $dsn      Data Source Name.
     * @param string|null $username Database username.
     * @param string|null $password Database password.
     * @param array  $options  PDO connection options.
     * @param bool   $persistent Whether to use a persistent connection.
     * @return void
     */
    public static function connect(string $dsn, ?string $username = null, ?string $password = null, array $options = [], bool $persistent = false): void
    {
        if ($persistent) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        Pool::add('default', $dsn, $username, $password, $options);
    }

    /**
     * Ensure the lazy connection is established.
     */
    private static function ensureConnected(): void
    {
        if (self::$pdo !== null) {
            return;
        }
        if (!Pool::has('default')) {
            throw new Exception("Spark ORM: Database not connected.");
        }
        try {
            self::$pdo = Pool::get('default');
        } catch (PDOException $e) {
            Logger::log(Level::FATAL, 'Spark DB Error: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Get active PDO instance.
     *
     * @param string|null $name Optional named connection (defaults to 'default').
     * @return PDO
     * @throws Exception If connection is not established.
     */
    public static function pdo(?string $name = null): PDO
    {
        if ($name !== null) {
            return Pool::get($name);
        }
        self::ensureConnected();
        return self::$pdo;
    }

    /**
     * Get the connection pool manager.
     *
     * @return class-string<Pool>
     */
    public static function pool(): string
    {
        return Pool::class;
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
    /**
     * Quote an SQL identifier safely (simple implementation).
     * Splits on dot and quotes each segment. Allows alphanumeric and underscore only.
     *
     * @param string $identifier
     * @return string
     */
    private function quoteIdent(string $identifier): string
    {
        $parts = explode('.', $identifier);
        $out = [];
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $part)) {
                throw new \InvalidArgumentException('Invalid identifier: ' . $identifier);
            }
            $out[] = "`" . $part . "`";
        }
        return implode('.', $out);
    }

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
        // Detect 2-arg shorthand by argument count
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $col = $this->quoteIdent($column);

        // Handle explicit NULL comparisons
        if ($value === null && strtoupper($operator) === '=') {
            $this->wheres[] = "$col IS NULL";
        } else {
            $this->wheres[] = "$col $operator ?";
            $this->bindings[] = $value;
        }

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
        $dir = strtoupper($direction);
        $dir = in_array($dir, ['ASC', 'DESC']) ? $dir : 'ASC';
        $col = $this->quoteIdent($column);
        $this->orderBy = " ORDER BY $col $dir";
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
     * @return mixed
     */
    public function first(): mixed
    {
        $sql = "SELECT * FROM " . $this->quoteIdent($this->table);
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $sql .= $this->orderBy ?? "";
        $sql .= " LIMIT 1";

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

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
        $sql = "SELECT * FROM " . $this->quoteIdent($this->table);
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        $sql .= $this->orderBy ?? "";
        $sql .= $this->limit ?? "";

        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        $results = $stmt->fetchAll();

        if ($this->modelClass) {
            return array_map(fn ($data) => new $this->modelClass($data, true), $results);
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
        $sql = "SELECT COUNT(*) FROM " . $this->quoteIdent($this->table);
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
        $col = $this->quoteIdent($column);
        $sql = "SELECT AVG($col) FROM " . $this->quoteIdent($this->table);
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
        $cols = array_keys($data);
        $columns = implode(', ', array_map(fn ($c) => $this->quoteIdent($c), $cols));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO " . $this->quoteIdent($this->table) . " ($columns) VALUES ($placeholders)";
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
            $fields[] = $this->quoteIdent($column) . " = ?";
            $values[] = $value;
        }
        $sql = "UPDATE " . $this->quoteIdent($this->table) . " SET " . implode(', ', $fields);
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
        $sql = "DELETE FROM " . $this->quoteIdent($this->table);
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }
        $stmt = Spark::pdo()->prepare($sql);
        return $stmt->execute($this->bindings);
    }
}
