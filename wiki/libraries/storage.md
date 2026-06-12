# Storage / Filesystem

The `SDF\Storage` facade provides a unified API for file operations across local and cloud storage backends.

## Configuration

Configure in `app/config/storage.php`:

```php
$config['storage'] = [
    'default' => 'local',
    'disks' => [
        'local' => [
            'root' => __DIR__ . '/../storage',
            'url' => '/storage',
        ],
        's3' => [
            'driver' => 's3',
            'key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
            'secret' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
            'region' => getenv('AWS_DEFAULT_REGION') ?: 'us-east-1',
            'bucket' => getenv('AWS_BUCKET') ?: '',
        ],
    ],
];
```

## Usage

Use the `Storage` facade for all operations. It resolves to the default disk from config.

```php
use SDF\Storage\Storage;

// Write
Storage::put('file.txt', 'Hello World');

// Read
$contents = Storage::get('file.txt');

// Check
if (Storage::exists('file.txt')) { /* ... */ }

// Delete
Storage::delete('file.txt');

// Meta
$size = Storage::size('file.txt');
$mime = Storage::mimeType('file.txt');
$url  = Storage::url('file.txt');
```

## LocalDriver

All operations run against a root directory on the local filesystem.

```php
use SDF\Storage\Drivers\LocalDriver;

$driver = new LocalDriver('/path/to/root', '/url/prefix');
$driver->put('avatar.jpg', $binaryData);
$files = $driver->files('uploads');           // one level
$all   = $driver->allFiles('uploads');        // recursive
$dirs  = $driver->directories('uploads');
$driver->copy('from.jpg', 'to.jpg');
$driver->move('tmp/old.jpg', 'final.jpg');
$driver->makeDirectory('new/path');
$driver->deleteDirectory('old/path');
```

## S3Driver

Requires `aws/aws-sdk-php` via Composer. Supports S3-compatible object stores (MinIO, DigitalOcean Spaces, etc.) via the `endpoint` config key.

```php
use SDF\Storage\Storage;

Storage::put('remote.txt', 'content');
$contents = Storage::get('remote.txt');
$url = Storage::url('remote.txt');
```

## Multiple Disks

Access different disks at runtime:

```php
$local = Storage::instance()->disk('local');
$s3    = Storage::instance()->disk('s3');
$s3->put('backup.sql', $dump);
```

## API Reference

| Method | Description |
|--------|-------------|
| `exists(string $path): bool` | Check if a file exists |
| `get(string $path): ?string` | Read file contents |
| `put(string $path, string $contents): bool` | Write a file |
| `stream(string $path, $resource): bool` | Write from a stream resource |
| `delete(string $path): bool` | Delete a file |
| `url(string $path): string` | Get the public URL |
| `size(string $path): int` | Get file size in bytes |
| `mimeType(string $path): string` | Get MIME type |
| `files(?string $directory): array` | List files (one level) |
| `allFiles(?string $directory): array` | List files (recursive) |
| `directories(?string $directory): array` | List subdirectories |
| `copy(string $from, string $to): bool` | Copy a file |
| `move(string $from, string $to): bool` | Move/rename a file |
| `makeDirectory(string $path): bool` | Create a directory |
| `deleteDirectory(string $path): bool` | Delete a directory and its contents |
