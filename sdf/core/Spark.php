<?php

namespace SDF;

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
     * @throws \Exception If connection is not established.
     */
    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \Exception("Spark ORM: Database not connected.");
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
     * @param string $operator Comparison operator (e.g., '=', '>', 'LIKE').
     * @param mixed  $value    Value to compare against.
     * @return self
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
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
        
        $stmt = Spark::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
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
}

namespace SDF\Spark;

use SDF\Spark;
use SDF\QueryBuilder;

/**
 * Base Active Record Model for Spark ORM.
 */
abstract class Model
{
    /** @var string $table Custom table name. If empty, pluralized class name is used. */
    protected static string $table = '';

    /**
     * Get the associated table name.
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
     * Start a new query for this model.
     * 
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return Spark::table(static::getTable());
    }

    /**
     * Retrieve all records for this model.
     * 
     * @return array
     */
    public static function all(): array
    {
        return self::query()->get();
    }
}
