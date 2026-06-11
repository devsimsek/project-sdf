# Spark ORM

Spark is the SDF v2.0.0 database layer. It provides a clean PDO-backed QueryBuilder and Active Record model base.

## Connecting

Spark auto-connects from `app/config/database.php` on the first query — no manual `connect()` call needed:

```php
// app/config/database.php
<?php
$config['database'] = [
    'driver'   => 'sqlite',      // mysql | pgsql | sqlite | sqlsrv | manual
    'host'     => '127.0.0.1',
    'name'     => 'myapp',
    'user'     => 'dbuser',
    'password' => 'secret',
    'port'     => 3306,
    'charset'  => 'utf8mb4',
    // For sqlite use either a path or :memory:
    'path'     => ':memory:',
];
```

The PDO connection is created lazily when `Spark::pdo()`, `Spark::table()`, or any query runs. Pages that never hit the database pay zero connection overhead.

### Manual connect (advanced)

For runtime DSN overrides or multiple connections:

```php
use SDF\Spark;

Spark::connect(
    dsn:      'mysql:host=127.0.0.1;dbname=myapp;charset=utf8mb4',
    username: 'root',
    password: 'secret',
    options:  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

Spark::connect('sqlite:/var/www/db.sqlite');
```

Configuration and DSN notes

- The config accepts either a full DSN via `dsn`, or a `path`/`host`+`name` combination. SQLite paths are auto-prefixed with `sqlite:` unless they already contain a colon (e.g. `sqlite::memory:`).

- `Spark::connect()` accepts nullable username/password to accommodate drivers (e.g., Windows-auth SQL Server or DSN-only connections).

QueryBuilder identifier quoting and NULL handling

- QueryBuilder quotes identifiers (table/column names) to reduce injection risk. Prefer using constant column names from your code (not raw user input). For complex cases, validate/whitelist identifiers before passing them to QueryBuilder.

- To compare against NULL explicitly use the full 3-arg form (e.g. `->where('deleted_at', 'IS', null)`) or helper methods (e.g. `whereNull()`); the builder detects the number of arguments to preserve explicit NULL comparisons.

## Defining a Model

```php
<?php
// app/models/User.php

use SDF\Spark\Model;

class User extends Model
{
    protected static string $table = 'users'; // optional - defaults to 'users'
}
```

```php
<?php
// app/models/Post.php

use SDF\Spark\Model;

class Post extends Model
{
    // Table name auto-resolved: 'posts'
}
```

## Querying

### Fetch All

```php
$users = User::all();
// SELECT * FROM users
```

### Where Clause (Chaining)

```php
$admins = User::query()
    ->where('role', '=', 'admin')
    ->where('active', '=', 1)
    ->get();
// SELECT * FROM users WHERE role = ? AND active = ?
```

### Insert

```php
User::query()->insert([
    'name'       => 'Jane Doe',
    'email'      => 'jane@example.com',
    'created_at' => date('Y-m-d H:i:s'),
]);
```

### Raw PDO (Advanced)

Access the underlying PDO instance for complex queries:

```php
$pdo  = Spark::pdo();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
$stmt->execute(['pending']);
$count = $stmt->fetchColumn();
```

## Real-World Example - Blog Controller

```php
<?php

class Blog extends SDF\Controller
{
    public function index(): void
    {
        // Fetch 10 most recent published posts
        $pdo  = \SDF\Spark::pdo();
        $stmt = $pdo->prepare(
            'SELECT p.*, u.name AS author FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.status = ? ORDER BY p.created_at DESC LIMIT 10'
        );
        $stmt->execute(['published']);
        $posts = $stmt->fetchAll();

        $this->fuse->with(compact('posts'))->render('blog/index');
    }

    public function show(int $id): void
    {
        $rows = Post::query()->where('id', '=', $id)->get();
        if (empty($rows)) {
            $this->response->status(404)->json(['error' => 'Not found']);
            return;
        }
        $this->fuse->with(['post' => $rows[0]])->render('blog/show');
    }

    public function store(): void
    {
        $body = $this->request->body();
        Post::query()->insert([
            'title'      => $body['title'],
            'content'    => $body['content'],
            'user_id'    => $body['user_id'],
            'status'     => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->response->status(201)->json(['created' => true]);
    }
}
```

## Migrations

Generate a migration stub:

```bash
php sdf/cli g migration create_users_table
```

This creates `app/migrations/create_users_table_TIMESTAMP.php`:

```php
<?php

class create_users_table_20240510123456
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(120) NOT NULL,
                email      VARCHAR(255) NOT NULL UNIQUE,
                role       ENUM('user','admin') DEFAULT 'user',
                active     TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS users");
    }
}
```

Run migrations:

```bash
php sdf/cli db migrate
```

Rollback last:

```bash
php sdf/cli db rollback
```

## QueryBuilder API

| Method | Description |
|---|---|
| `Spark::table('name')` | Start a new query |
| `->where($col, $op, $val)` | Add a WHERE clause |
| `->get()` | Execute SELECT, return array |
| `->insert($data)` | Execute INSERT |
| `Spark::pdo()` | Get raw PDO instance |
| `Model::all()` | Fetch all rows for a model |
| `Model::query()` | Start a QueryBuilder for a model |
