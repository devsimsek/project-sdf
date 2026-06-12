# Model Timestamps & Soft Deletes

The Spark ORM Model provides automatic `created_at`/`updated_at` timestamp management and soft delete support.

## Timestamps

By default, `$timestamps = true`. On create, `created_at` and `updated_at` are set to the current datetime. On update, `updated_at` is refreshed automatically.

```php
use SDF\Spark\Model;

class User extends Model
{
    protected static string $table = 'users';
}
```

Your table must have `created_at` and `updated_at` columns (TEXT or DATETIME):

```php
$user = new User(['name' => 'Alice']);
$user->save();

echo $user->created_at; // '2026-06-11 12:00:00'
echo $user->updated_at; // '2026-06-11 12:00:00'

sleep(1);
$user->name = 'Alicia';
$user->save();
echo $user->updated_at; // updated timestamp
```

Disable timestamps by setting `$timestamps = false`:

```php
class LogEntry extends Model
{
    protected static string $table = 'logs';
    protected static bool $timestamps = false;
}
```

## Soft Deletes

Enable soft deletes by setting `$softDeletes = true`. Your table needs a nullable `deleted_at` column.

```php
class Post extends Model
{
    protected static string $table = 'posts';
    protected static bool $softDeletes = true;
}
```

### Soft-deleting a record

```php
$post = Post::find(1);
$post->delete(); // sets deleted_at to current datetime
$post->trashed(); // true
```

### Normal queries exclude soft-deleted records

```php
$post = Post::find(1); // null — hidden
$posts = Post::all();  // only non-deleted
```

### Including soft-deleted records

```php
$post = Post::withTrashed()->where('id', 1)->first();
```

### Only soft-deleted records

```php
$trashed = Post::onlyTrashed()->get();
```

### Restoring

```php
$post = Post::withTrashed()->where('id', 1)->first();
$post->restore();       // sets deleted_at back to null
$post->trashed();       // false
Post::find(1);           // found again
```

### Permanent deletion

```php
$post->forceDelete(); // removes the row entirely
```

## API Reference

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$timestamps` | `bool` | `true` | Auto-manage created_at/updated_at |
| `$softDeletes` | `bool` | `false` | Soft delete via deleted_at column |

### Methods

| Method | Description |
|--------|-------------|
| `delete(): bool` | Soft-deletes (when enabled) or hard-deletes |
| `forceDelete(): bool` | Permanently removes the row |
| `trashed(): bool` | Check if the model is soft-deleted |
| `restore(): bool` | Undo a soft delete |
| `withTrashed(): QueryBuilder` | Include soft-deleted records in query |
| `onlyTrashed(): QueryBuilder` | Only query soft-deleted records |
