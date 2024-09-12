# SDF Sorm Documentation

Sorm provides a comprehensive orm classes for working with databases in sdf. These classes are;

- [Sorm Class](#sorm-class)
- [Model Class](#model-class)
- [Migration Class](#migration-class)
- [Seeder Class](#seeder-class)

## Sorm Class

> The recommended usage of Sorm is through its model class, extend your models using SDF\Sorm\Model.

### Overview

The `Sorm` class is a simple ORM (Object-Relational Mapping) tool for working with databases in sdf. It provides basic
CRUD operations (Create, Read, Update, Delete) and utilities for handling database connections and queries.

### Properties

- **`private PDO $pdo`**: Holds the database connection object.
- **`private string $table`**: Holds the name of the table associated with this instance.
- **`private int $connection = 0`**: Flag to track if a database connection has been established.

### Methods

#### 1. `connect()`

Establishes a connection to the database.

```php
public function connect(
  string $host,
  string $dbname,
  string $username,
  string $password,
  string $table
): self
```

- **Parameters**:
  - `$host`: The hostname for the database.
  - `$dbname`: The name of the database.
  - `$username`: The database username.
  - `$password`: The database password.
  - `$table`: The table to operate on.
- **Returns**: Returns the current instance of `Sorm`.
- **Description**: Initializes a connection to a MySQL database using PDO and sets the table name.

#### 2. `create()`

Inserts a new record into the table.

```php
public function create(array $data): bool
```

- **Parameters**:
  - `$data`: An associative array of data to insert into the table (column => value).
- **Returns**: Boolean indicating success or failure.
- **Description**: Generates an `INSERT INTO` SQL statement based on the provided data and executes it.

#### 3. `read()`

Reads records from the table.

```php
public function read(array $conditions = []): array
```

- **Parameters**:
  - `$conditions`: An optional associative array to filter the results (column => value).
- **Returns**: An array of results from the table or an empty array if no connection exists.
- **Description**: Generates a `SELECT * FROM` SQL query. If conditions are provided, they are added as a `WHERE`
  clause.

#### 4. `update()`

Updates records in the table.

```php
public function update(array $data, array $conditions): bool
```

- **Parameters**:
  - `$data`: The associative array of data to update (column => value).
  - `$conditions`: An associative array for the `WHERE` clause to specify which records to update.
- **Returns**: Boolean indicating success or failure.
- **Description**: Generates an `UPDATE` SQL query using the provided data and conditions.

#### 5. `delete()`

Deletes records from the table.

```php
public function delete(array $conditions): bool
```

- **Parameters**:
  - `$conditions`: An associative array to define which records to delete (column => value).
- **Returns**: Boolean indicating success or failure.
- **Description**: Generates a `DELETE FROM` SQL query using the provided conditions.

#### 6. `query()`

Executes a raw SQL query.

```php
public function query(string $sql, array $params = []): array
```

- **Parameters**:
  - `$sql`: The raw SQL query to execute.
  - `$params`: An optional array of parameters for the query.
- **Returns**: An array of results from the query.
- **Description**: Prepares and executes a raw SQL query using PDO and returns the results.

#### 7. `join()`

Performs a SQL JOIN query between two tables.

```php
public function join(
  string $table,
  string $on,
  array $select = [],
  string $type = "INNER",
  array $conditions = []
): array
```

- **Parameters**:
  - `$table`: The table to join with.
  - `$on`: The condition for the `ON` clause.
  - `$select`: An optional array of columns to select.
  - `$type`: The type of join (INNER, LEFT, RIGHT).
  - `$conditions`: An optional array of conditions for the `WHERE` clause.
- **Returns**: An array of results.
- **Description**: Generates a `JOIN` SQL query and retrieves the result set.

---

## Model Class

### Overview

The `Model` class serves as an abstract base class for specific database models. It provides static CRUD methods that
interact with the `SDF\Sorm` ORM.

### Methods

#### 1. `setDatabase()`

Sets the database connection for the model.

```php
public static function setDatabase(Sorm $db): void
```

- **Parameters**:
  - `$db`: An instance of the `Sorm` class.
- **Description**: Associates the model with a specific database connection.

#### 2. `tableName()`

Returns the table name for the model.

```php
public static function tableName(): string
```

- **Returns**: The name of the table.
- **Description**: Returns the table name associated with the model. Assumes that the table name is the plural form of
  the model class name.

#### 3. `create()`

Inserts a new record into the table.

```php
public static function create(array $data): bool
```

- **Parameters**:
  - `$data`: An associative array of data to insert.
- **Returns**: Boolean indicating success or failure.

#### 4. `read()`

Reads records from the table.

```php
public static function read(array $conditions = []): array
```

- **Parameters**:
  - `$conditions`: Optional associative array to filter results.
- **Returns**: An array of results.

#### 5. `update()`

Updates records in the table.

```php
public static function update(array $data, array $conditions): bool
```

- **Parameters**:
  - `$data`: Associative array of data to update.
  - `$conditions`: Array specifying which records to update.

#### 6. `delete()`

Deletes records from the table.

```php
public static function delete(array $conditions): bool
```

- **Parameters**:
  - `$conditions`: Conditions for deleting records.

#### 7. `query()`

Executes a raw SQL query.

```php
public static function query(string $sql, array $params = []): array
```

#### 8. `join()`

Performs a SQL JOIN query.

```php
public static function join(
  string $table,
  string $on,
  array $select = [],
  string $type = "INNER",
  array $conditions = []
): array
```

---

## Migration Class

### Overview

The `Migration` class is used to create and manage database migrations.

> Note: Migrations can be generated using the `./sdf/cli g migration <name>` command.

### Methods

#### 1. `up()`

Defines the logic for migrating up.

```php
public function up(): void
```

#### 2. `down()`

Defines the logic for migrating down.

```php
public function down(): void
```

#### 3. `execute()`

Executes a raw SQL query.

```php
protected function execute(string $sql): void
```

---

## Seeder Class

### Overview

The `Seeder` class is used to seed a database with initial data.

### Methods

#### 1. `run()`

Runs the seeding operation.

```php
public function run(): void
```
