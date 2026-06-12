---
name: project-sdf
description: Build and run SDF PHP projects — CLI, routing, Spark ORM, Fuse views, auth, cache, queues, mail, Docker deployment
license: MIT
compatibility: opencode
metadata:
  audience: developers
  framework: sdf
---

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
php sdf/cli agent install                # install this skill locally
php sdf/cli agent install global         # install this skill globally
```

## Project structure
```
├── app/
│   ├── config/          # PHP/JSON config files → $config['key'] = [...]
│   ├── controllers/     # your controllers
│   ├── handlers/        # error handlers
│   ├── models/          # your models
│   └── views/           # Fuse templates (.fuse.php)
├── sdf/                 # framework core (don't touch)
├── public/              # static assets (images, css, js)
├── storage/             # logs, cache, uploads
├── tests/
├── vendor/
├── .env                 # environment variables
├── index.php            # entry point
└── routes.php           # route definitions
```

## Routing
```php
Router::add('/', function() { return 'Hello'; });
Router::add('/users', '\App\Controllers\UserController@index');
Router::add('/post/{id}', '\App\Controllers\PostController@show');
Router::add('/user/{uuid}', 'UserController@show');
Router::add('/login', 'AuthController@login', 'GET')->name('login');
```
Route placeholders: `{id}`, `{uuid}`, `{uuid_simple}`, `{slug}`, `{date}`, `{all}`, `{hex}`, `{alpha}`, `{alnum}`, `{word}`, `{segment}`, `{file}`, `{bool}`, `{time}`, `{datetime}`, `{url}`, `{num}`.

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
@extends('layouts.app')
@section('content')
    <h1>{{ $title }}</h1>
    @include('partials.nav')
@endsection
```

## Database (Spark ORM)
```php
// Query builder
$users = Spark::table('users')->where('active', 1)->get();
$user  = Spark::table('users')->find(1);
$posts = Spark::table('posts')->where('user_id', $id)->paginate(15);

// Models
class Post extends \SDF\Spark\Model {
    protected string $table = 'posts';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = false;

    public function author() { return $this->belongsTo(User::class, 'user_id'); }
    public function comments() { return $this->hasMany(Comment::class, 'post_id'); }
    public function tags() { return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id'); }
}

$post = Post::find(1);
$post->title = 'Updated';
$post->save();
```

## Authentication
- **SessionGuard** — session-based login
- **JwtGuard** — stateless HS256 JWT with refresh tokens
```php
Router::add('/dashboard', 'DashboardController@index')->middleware('auth');
```
Middleware stack: `CsrfMiddleware`, `CorsMiddleware`, `RateLimitMiddleware`, `AuthMiddleware`.

## Cache (PSR-16)
```php
use SDF\Cache\Cache;
Cache::set('key', 'value', 3600);
$value = Cache::get('key', 'default');
```
Drivers: `file`, `redis`, `memcached`.

## Validation
```php
$v = new \SDF\Validation\Validator($data, [
    'email' => 'required|email',
    'name'  => 'required|min:3|max:255',
]);
```
19 rules: required, email, min, max, between, numeric, integer, string, boolean, array, alpha, alpha_num, url, in, confirmed, same, different, regex, nullable.

## Components
- **Encryption**: AES-256-CBC + HMAC-SHA256
- **PSR-7 HTTP**: immutable `ServerRequest` and `Response`
- **Storage**: `Storage::disk('local')->put('file.txt', 'content')`
- **Mail**: `Mail::send(...)` via configured driver
- **Queues**: `Queue::dispatch(Job::class, $payload)`
- **Events**: PSR-14 `EventDispatcher::dispatch(new UserRegistered($user))`
- **Logging**: PSR-3 `Logger::info('message')`
- **Schema Builder**: `Schema::create('table', function($table) { ... })`
- **Localization**: `__('messages.welcome')`

## Deployment
```bash
docker compose up -d    # starts FrankenPHP + MySQL 8.4 + Redis 7
```
Multi-stage Dockerfile, Caddyfile included. Set `SDF_ENV=production` for production mode.

## When to use me
Use this skill when working on an SDF PHP project: scaffolding code, defining routes, building models and migrations, setting up authentication, managing caches, or deploying with Docker. Ask clarifying questions if the task is ambiguous.
