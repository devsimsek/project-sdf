<?php

// DEPRECATION NOTICE!
// Sorm will be deprecated within sdf 2.0

namespace SDF;

use PDO;
use PDOException;

/**
 * smskSoft SDF Sorm
 * Simple Object Relational Mapping
 * A simple ORM for project Sdf.
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Sorm.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/sorm
 * @since       Version 1.0
 * @filesource
 */
class Sorm
{
  private PDO $pdo;
  private string $table;
  private int $connection = 0;

  /**
   * Connect to the database.
   * @param string $host
   * @param string $dbname
   * @param string $username
   * @param string $password
   * @param string $table
   * @return void
   */
  public function connect(
    string $host,
    string $dbname,
    string $username,
    string $password,
    string $table
  ): self
  {
    try {
      $this->pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
      );
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->table = $table;
      $this->connection = 1;
    } catch (PDOException $e) {
      die("Database connection failed: " . $e->getMessage());
    }

    return $this;
  }

  /**
   * Create a new record.
   * @param array<int,mixed> $data
   * @return bool
   */
  public function create(array $data): bool
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return false;
    }
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
    $stmt = $this->pdo->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    return $stmt->execute();
  }

  /**
   * Read records.
   * @param array<int,mixed> $conditions
   * @return array|bool
   */
  public function read(array $conditions = []): array
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return [];
    }

    $sql = "SELECT * FROM {$this->table}";
    if (!empty($conditions)) {
      $clauses = [];
      foreach ($conditions as $key => $value) {
        $clauses[] = "$key = :$key";
      }
      $sql .= " WHERE " . implode(" AND ", $clauses) . ";";
    } else {
      $sql .= ";";
    }

    $stmt = $this->pdo->prepare($sql);

    foreach ($conditions as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Update records.
   * @param array<int,mixed> $data
   * @param array<int,mixed> $conditions
   */
  public function update(array $data, array $conditions): bool
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return false;
    }
    $setClauses = [];
    foreach ($data as $key => $value) {
      $setClauses[] = "$key = :$key";
    }
    $sql = "UPDATE {$this->table} SET " . implode(", ", $setClauses);

    if (!empty($conditions)) {
      $conditionClauses = [];
      foreach ($conditions as $key => $value) {
        $conditionClauses[] = "$key = :cond_$key";
      }
      $sql .= " WHERE " . implode(" AND ", $conditionClauses);
    }

    $stmt = $this->pdo->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }
    foreach ($conditions as $key => $value) {
      $stmt->bindValue(":cond_$key", $value);
    }

    return $stmt->execute();
  }

  /**
   * Delete records.
   * @param array<int,mixed> $conditions
   */
  public function delete(array $conditions): bool
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return false;
    }
    $sql = "DELETE FROM {$this->table}";
    if (!empty($conditions)) {
      $clauses = [];
      foreach ($conditions as $key => $value) {
        $clauses[] = "$key = :$key";
      }
      $sql .= " WHERE " . implode(" AND ", $clauses);
    }

    $stmt = $this->pdo->prepare($sql);

    foreach ($conditions as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    return $stmt->execute();
  }

  /**
   * Execute a raw query.
   * checkpoint: this should be private
   * @param string $sql
   * @param array<int,mixed> $params
   * @return array|bool
   */
  public function query(string $sql, array $params = []): array
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return [];
    }

    $stmt = $this->pdo->prepare($sql);

    if (array_keys($params) !== range(0, count($params) - 1)) {
      foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
      }
    } else {
      foreach ($params as $key => $value) {
        switch (gettype($value)) {
          case "integer":
            $stmt->bindValue($key + 1, $value, PDO::PARAM_INT);
            break;
          case "boolean":
            $stmt->bindValue($key + 1, $value, PDO::PARAM_BOOL);
            break;
          case "NULL":
            $stmt->bindValue($key + 1, $value, PDO::PARAM_NULL);
            break;
          default:
            $stmt->bindValue($key + 1, $value);
        }
      }
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Join tables.
   * @param string $table
   * @param string $on
   * @param array<int,mixed> $select
   * @param string $type
   * @param array<int,mixed> $conditions
   * @return array|bool
   */
  public function join(
    string $table,
    string $on,
    array  $select = [],
    string $type = "INNER",
    array  $conditions = []
  ): array
  {
    if (!$this->connection) {
      error_log("Connection not established");
      return [];
    }

    $select = empty($select) ? "*" : implode(", ", $select);
    $sql = "SELECT $select FROM {$this->table} $type JOIN $table ON $on";
    if (!empty($conditions)) {
      $clauses = [];
      foreach ($conditions as $key => $value) {
        $clauses[] = "$key = :$key";
      }
      $sql .= " WHERE " . implode(" AND ", $clauses);
    }

    $stmt = $this->pdo->prepare($sql);
    foreach ($conditions as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

namespace SDF\Sorm;

use PDO;
use PDOException;
use ReflectionClass;

abstract class Model
{
  protected static \SDF\Sorm $db;

  public static function setDatabase(\SDF\Sorm $db): void
  {
    self::$db = $db;
  }

  public static function tableName(): string
  {
    $class = static::class;
    return strtolower(end(explode("\\", $class))) . "s"; // Assumes table name is pluralized class name
  }

  /**
   * @param array<int,mixed> $data
   */
  public static function create(array $data): bool
  {
    return self::$db->create($data);
  }

  /**
   * @param array<int,mixed> $conditions
   */
  public static function read(array $conditions = []): array
  {
    return self::$db->read($conditions);
  }

  /**
   * @param array<int,mixed> $data
   * @param array<int,mixed> $conditions
   */
  public static function update(array $data, array $conditions): bool
  {
    return self::$db->update($data, $conditions);
  }

  /**
   * @param array<int,mixed> $conditions
   */
  public static function delete(array $conditions): bool
  {
    return self::$db->delete($conditions);
  }

  /**
   * checkpoint: this should be private
   * @param string $sql
   * @param array<int,mixed> $params
   */
  public static function query(string $sql, array $params = []): array
  {
    return self::$db->query($sql, $params);
  }

  /**
   * @param string $table
   * @param string $on
   * @param array<int,mixed> $select
   * @param string $type
   * @param array<int,mixed> $conditions
   */
  public static function join(
    string $table,
    string $on,
    array  $select = [],
    string $type = "INNER",
    array  $conditions = []
  ): array
  {
    return self::$db->join($table, $on, $select, $type, $conditions);
  }
}

class Migration
{
  protected PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  public function up(): void
  {
    // To be implemented in migrations
  }

  public function down(): void
  {
    // To be implemented in migrations
  }

  protected function execute(string $sql): void
  {
    $this->pdo->exec($sql);
  }
}

class Seeder
{
  protected PDO $pdo;
  protected string $table;

  public function __construct(PDO $pdo, string $table)
  {
    $this->pdo = $pdo;
    $this->table = $table;
  }

  public function run(): void
  {
    // To be implemented in seeders
  }
}

class Migrator
{
  protected PDO $pdo;
  protected string $table;
  protected array $fields = [];
  protected string $model;
  protected string $sql;

  // checkpoint: make a function that return's migration in model files. etc. provideTable etc.
  // Constructor to extract fields from the model
  public function __construct(string $model, PDO $pdo)
  {
    $this->pdo = $pdo;
    $this->model = $model;

    // Ensure the model class exists
    if (!class_exists($model)) {
      throw new \Exception(
        "Can't find the $model model, in order to use Migrator, you need to provide model classes."
      );
    }

    // Use Reflection to extract constructor parameters
    $reflection = new ReflectionClass($model);
    $constructor = $reflection->getConstructor();
    if ($constructor) {
      $params = $constructor->getParameters();
      foreach ($params as $p) {
        $this->fields[$p->getName()] = [
          "type" => $p->getType()?->getName(),
          "default" => $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null
        ];
      }
    } else {
      throw new \Exception(
        "Model $model has no constructor. Please rewrite your model with a constructor that initializes all fields."
      );
    }

    // Get the table name from a method on the model (e.g., tableName())
    if (!method_exists($model, 'tableName')) {
      throw new \Exception("Model $model must define a tableName() method.");
    }

    $this->table = $model::tableName();
  }

  // Converts PHP types to SQL types
  protected function typeToSql(mixed $type): string|false
  {
    switch ($type) {
      case 'int':
        return 'INT';
      case 'string':
        return 'VARCHAR(255)';
      case 'bool':
        return 'BOOLEAN';
      case 'float':
        return 'FLOAT';
      case 'DateTime':
        return 'DATETIME';
      default:
        return false;
    }
  }

  // Run migration, generate SQL query to create the table
  public function run(int $mode = 1): string|null
  {
    // Start creating SQL for creating a table
    $sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` (";
    $columns = [];

    // Generate SQL for each field
    foreach ($this->fields as $name => $attributes) {
      $type = $this->typeToSql($attributes['type']);
      if (!$type) {
        throw new \Exception("Unsupported type for field '$name'");
      }

      // Check if the field is an "id" field, set it as the primary key with auto-increment
      if (strtolower($name) === "id") {
        $type = "INT AUTO_INCREMENT PRIMARY KEY";
      }

      $column = "`$name` $type";

      // Check for default value
      if ($attributes['default'] !== null) {
        $default = is_string($attributes['default']) ? "'{$attributes['default']}'" : $attributes['default'];
        $column .= " DEFAULT $default";
      }

      $columns[] = $column;
    }

    // Complete the SQL query by joining columns
    $sql .= implode(", ", $columns) . ");";
    $this->sql = $sql;

    if ($mode === 0) {
      return $sql;
    }

    try {
      $this->pdo->exec($this->sql);
      echo "Table '$this->table' created successfully.\n";
    } catch (PDOException $e) {
      throw new \Exception("Failed to create table: " . $e->getMessage());
    }

    return null;
  }
}
