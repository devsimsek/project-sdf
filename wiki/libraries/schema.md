# Schema Builder

Fluent table creation and migration support for MySQL and SQLite. Namespace: `SDF\Schema`.

## Creating Tables

Pass a table name and a callback receiving a `Blueprint` instance to define columns:

```php
use SDF\Schema\Schema;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('name');
    $table->timestamps();
});
```

## Column Types

| Method | SQL Type (MySQL) | SQL Type (SQLite) |
|---|---|---|
| `id()` | `BIGINT AUTO_INCREMENT` | `INTEGER PRIMARY KEY AUTOINCREMENT` |
| `string($name, $length = 255)` | `VARCHAR(length)` | `VARCHAR(length)` |
| `integer($name, $length = 11)` | `INT(length)` | `INT(length)` |
| `bigInteger($name)` | `BIGINT` | `BIGINT` |
| `boolean($name)` | `TINYINT(1)` | `INTEGER` |
| `text($name)` | `TEXT` | `TEXT` |
| `float($name, $precision = 8, $scale = 2)` | `FLOAT(precision, scale)` | `FLOAT(precision, scale)` |
| `decimal($name, $precision = 8, $scale = 2)` | `DECIMAL(precision, scale)` | `DECIMAL(precision, scale)` |
| `date($name)` | `DATE` | `DATE` |
| `dateTime($name)` | `DATETIME` | `DATETIME` |

## Column Modifiers

Chain modifiers after a column definition. Each applies to the **last** column added:

```php
$table->string('email')->unique();
$table->string('middle_name')->nullable();
$table->boolean('is_admin')->default(false);
$table->text('bio')->nullable()->default('');
```

| Modifier | Description |
|---|---|
| `->nullable()` | Allow `NULL` values |
| `->default($value)` | Set a default value |
| `->unique()` | Add a `UNIQUE` constraint |

## Constraints

### Primary Key

```php
$table->primary('id');
$table->primary(['user_id', 'role']); // composite
```

Note: `id()` auto-registers the column as primary key — no manual `primary()` needed.

### Foreign Key

```php
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade')
      ->onUpdate('cascade');
```

Defaults: `NO ACTION` for both `onDelete` and `onUpdate`.

## Convenience Columns

```php
// Adds created_at DATETIME + updated_at DATETIME
$table->timestamps();

// Adds nullable deleted_at DATETIME
$table->softDeletes();
```

## Modifying Existing Tables

`Schema::table()` issues `ALTER TABLE ADD COLUMN` statements:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();
    $table->boolean('subscribed')->default(true);
});
```

Foreign keys can also be added via `Schema::table()`.

## Table Inspection

```php
if (Schema::hasTable('users')) { /* ... */ }

if (Schema::hasColumn('users', 'email')) { /* ... */ }

Schema::drop('users');       // DROP TABLE IF EXISTS
Schema::dropIfExists('users'); // alias
```

## Driver Support

The builder auto-detects the PDO driver (`mysql` or `sqlite`) and adjusts identifier quoting and type mappings accordingly. SQLite uses double-quotes for identifiers; MySQL uses backticks.
