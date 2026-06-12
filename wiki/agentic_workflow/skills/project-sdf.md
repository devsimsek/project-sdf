# Project SDF — Agent Skill

## Quick start
```bash
composer install
php sdf/cli serve -p 8000                # dev server at localhost:8000
php sdf/cli serve -p 8000 --live         # with auto-reload
php sdf/cli serve -p 8000 --clear-cache  # clear caches before serving
```

## CLI reference
```bash
php sdf/cli g controller Home            # generate a controller
php sdf/cli g model Post                 # generate a model
php sdf/cli g migration create_posts_table  # generate a migration
php sdf/cli g test HomeController --type=controller --namespace=App\\Controllers\\User
php sdf/cli db migrate                   # run pending migrations
php sdf/cli cache clear                  # flush all caches
php sdf/cli format --dry-run -v          # check code style
php sdf/cli swagger generate             # generate OpenAPI spec
```

## Project structure
```
├── app/
│   ├── config/          # PHP/JSON config files → $config['key'] = [...]
│   ├── controllers/     # your controllers
│   ├── handlers/        # error handlers (eh_pathNotFound, eh_methodNotAllowed)
│   ├── models/          # your models
│   └── views/           # Fuse templates (.fuse.php)
├── sdf/                 # framework core (don't touch)
├── public/              # static assets (images, css, js)
├── storage/             # logs, cache, uploads
├── tests/
├── vendor/
├── .env                 # environment variables
├── index.php            # entry point (defines SDF, SDF_ENV, etc.)
└── routes.php           # route definitions
```

## Routing (`routes.php`)
```php
// Closure
Router::add('/', function() { return 'Hello'; });

// Controller@method (namespaced)
Router::add('/users', '\App\Controllers\UserController@index');

// With params
Router::add('/post/{id}', '\App\Controllers\PostController@show');

// HTTP method filter
Router::add('/users', 'UserController@store', 'POST');

// Named routes
Router::add('/login', 'AuthController@login', 'GET')->name('login');
```

## Controllers
```php
namespace App\Controllers;
class HomeController {
    public function index() {
        return view('home', ['title' => 'SDF']);
    }
}
```

## Views (Fuse — Blade-like syntax)
```php
<!-- layouts/app.fuse.php -->
<html><body>
    @yield('content')
</body></html>

<!-- home.fuse.php -->
@extends('layouts.app')
@section('content')
    <h1>{{ $title }}</h1>
    @include('partials.nav')
@endsection
```

## Database (Spark ORM)
### Config (`app/config/database.php`)
```php
$config['database'] = [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'charset'  => 'utf8mb4',
];
```

### Query builder
```php
$users = Spark::table('users')->where('active', 1)->get();
$user  = Spark::table('users')->find(1);
$posts = Spark::table('posts')->where('user_id', $id)->paginate(15);
```

### Models
```php
class Post extends \SDF\Spark\Model {
    protected string $table = 'posts';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;    // adds created_at/updated_at
    protected bool $softDelete = false;   // adds deleted_at

    // Relationships
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function comments() {
        return $this->hasMany(Comment::class, 'post_id');
    }
    public function tags() {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
    }
}

// Usage
$post = Post::find(1);
$post->title = 'Updated';
$post->save();
$post->delete();          // soft-delete if enabled
$post->forceDelete();     // permanent delete
Post::withTrashed()->get();
Post::onlyTrashed()->get();
```

## Authentication
### Guard types
- **SessionGuard** — session-based login. Configure in `app/config/auth.php`.
- **JwtGuard** — stateless HS256 JWT with refresh tokens.

### Protecting routes
```php
Router::add('/dashboard', 'DashboardController@index')->middleware('auth');
```

### Auth middleware stack
- `CsrfMiddleware` — validates `X-CSRF-TOKEN` or `_token` POST field. 419 on mismatch.
- `CorsMiddleware` — configurable origins/methods/headers. OPTIONS → 204.
- `RateLimitMiddleware` — 60 req/min per IP+route. 429 with `Retry-After`.
- `AuthMiddleware` — 401 if unauthenticated.

## Cache (PSR-16)
```php
use SDF\Cache\Cache;

Cache::set('key', 'value', 3600);
$value = Cache::get('key', 'default');
Cache::delete('key');
Cache::clear();
```

Drivers: `file`, `redis`, `memcached`. Set in `app/config/cache.php`.

## Validation
```php
$validator = new \SDF\Validation\Validator($data, [
    'email' => 'required|email',
    'name'  => 'required|min:3|max:255',
    'age'   => 'numeric|between:18,99',
]);
if ($validator->fails()) {
    $errors = $validator->errors();
}
```

19 rules: required, email, min, max, between, numeric, integer, string, boolean, array, alpha, alpha_num, url, in, confirmed, same, different, regex, nullable.

## Components
- **Encryption**: AES-256-CBC + HMAC-SHA256. `Encrypter::encrypt($plaintext)`, `decrypt($ciphertext)`. Key from `APP_KEY` env (32 bytes).
- **PSR-7 HTTP**: `ServerRequest` and `Response` — immutable (`with*` returns new instance). Legacy mutable classes also available.
- **Storage**: `Storage::disk('local')->put('file.txt', 'content')`. Drivers: `local`, `s3`.
- **Mail**: `Mail::send(...)` via configured driver.
- **Queues**: `Queue::dispatch(Job::class, $payload)` via `database` or `redis` driver.
- **Events**: PSR-14 `EventDispatcher::dispatch(new UserRegistered($user))`.
- **Logging**: PSR-3 `Logger::info('message')`, `Logger::error('message', $context)`.
- **Schema Builder**: `Schema::create('table', function($table) { ... })` for migrations.
- **Localization**: `__('messages.welcome')` with language files in `app/lang/`.

## Deployment
### Docker
```bash
docker compose up -d    # starts FrankenPHP + MySQL 8.4 + Redis 7
```

Multi-stage Dockerfile: `base` (composer --no-dev), `dev` (xdebug), `production` (OPcache). Set `SDF_ENV=production` for production mode.

### Static files
Served automatically in development mode. In production, use Caddy (included in Docker) or nginx to serve `public/`.

## Wiki docs
Full documentation at `wiki/`:
- `wiki/libraries/` — component docs (auth, cache, csrf, cors, ratelimit, encryption, validation, session, flash, http, swagger, log, schema, events, localization, mail, queue, storage)
- `wiki/app/tutorials/` — tutorials (authentication, blog, docker-frankenphp)
- `wiki/sdf/` — CLI commands docs
