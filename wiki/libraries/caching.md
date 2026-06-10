# Caching

The SDF caching layer provides a unified, PSR-16 (SimpleCache) compliant interface with support for file, Redis, and Memcached backends.

## Configuration

Configure the cache driver in `app/config/cache.php`:

```php
$config['cache'] = [
    'driver' => 'file', // file, redis, memcached

    'file' => [
        'path' => sys_get_temp_dir() . '/sdf_cache/',
        'prefix' => 'sdf_cache_',
    ],

    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 2.5,
        'prefix' => 'sdf_cache:',
    ],

    'memcached' => [
        'host' => '127.0.0.1',
        'port' => 11211,
        'prefix' => 'sdf_cache_',
    ],
];
```

## Usage

### Basic get/set

```php
use SDF\Cache\Cache;

Cache::set('key', 'value', 3600); // TTL in seconds
$value = Cache::get('key', 'default');

Cache::has('key');              // bool
Cache::delete('key');           // bool
Cache::forget('key');           // alias for delete
Cache::flush();                 // clear all
```

### Multiple operations

```php
Cache::setMultiple(['a' => 1, 'b' => 2]);
$values = Cache::getMultiple(['a', 'b', 'c'], 0);
Cache::deleteMultiple(['a', 'b']);
```

### Tagging

Tags allow grouping cache entries for bulk invalidation:

```php
Cache::tags(['people'])->set('user_1', 'Alice');
Cache::tags(['people'])->set('user_2', 'Bob');

// Invalidate all entries tagged with 'people'
Cache::tags(['people'])->forget('user_1');

// Or retrieve / clear by tag
$driver = Cache::driver();
if (method_exists($driver, 'forgetTags')) {
    $driver->forgetTags(['people']);
}
```

### TTL

TTL can be an integer (seconds), `null` (no expiration), or a `DateInterval`:

```php
use DateInterval;

Cache::set('perm', 'forever');                 // no TTL
Cache::set('temp', 'ephemeral', 300);          // 5 minutes
Cache::set('bio', 'degraded', new DateInterval('PT1H')); // 1 hour
```

### Graceful fallback

Redis and Memcached drivers degrade gracefully when their extensions are missing or the server is unreachable — they return defaults and return `false` for writes. No exceptions are thrown.

```php
$driver = new RedisDriver(['host' => 'unreachable']);
$driver->isAvailable();   // false
$driver->get('anything', 'fallback'); // 'fallback'
$driver->set('x', 'y');   // false
```

## Drivers

| Driver      | Extension required | Storage           |
|-------------|-------------------|--------------------|
| FileDriver  | none              | Serialized files   |
| RedisDriver | redis             | Redis key-value    |
| MemcachedDriver | memcached     | Memcached          |

## Integration

The Fuse view engine and framework config cache use this layer automatically when available.
