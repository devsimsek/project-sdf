<?php
namespace SDF;

use PDO;
use PDOException;

// checkpoint, allow phpcli to run this file

/**
 * Sorm (Simple Object Relational Mapping) is a simple ORM for projekt Sdf.
 *
 * @package Sorm
 * @version     v1.0.0
 * @author  Sorm
 * @license devsimsek.mit-license.org
 * @link
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
    ): self {
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
     * @return array|bool
     * @param array<int,mixed> $conditions
     */
    public function join(
        string $table,
        string $on,
        array $select = [],
        string $type = "INNER",
        array $conditions = []
    ): array {
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
        array $select = [],
        string $type = "INNER",
        array $conditions = []
    ): array {
        return self::$db->join($table, $on, $select, $type, $conditions);
    }
}

class Migration
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
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

/*
// Example usage
$pdo = new PDO(
    "mysql:host=localhost;dbname=beta_stories;charset=utf8",
    "root",
    ""
);

// Set database for model
$sorm = new Sorm("localhost", "test_db", "root", "password", "users");
Model::setDatabase($sorm);

// Define a model
class User extends Model
{
    public static function tableName(): string
    {
        return "users"; // Override table name if needed
    }
}

// Example Migration
class CreateUserTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE
        )";
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE users";
        $this->execute($sql);
    }
}

// Example Seeder
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $sql =
            "INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com')";
        $this->pdo->exec($sql);
    }
}

// Run migrations
$migration = new CreateUserTable($pdo, "users");
$migration->up();

// Seed database
$seeder = new UserSeeder($pdo, "users");
$seeder->run();

// Create a new user
User::create(["name" => "Jane Doe", "email" => "jane@example.com"]);

// Read users
$users = User::read();
print_r($users);

// Update user
User::update(["email" => "jane.doe@example.com"], ["name" => "Jane Doe"]);

// Delete user
User::delete(["name" => "Jane Doe"]);
*/
