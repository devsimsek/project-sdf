---
name: project-sdf
description: Build and run SDF PHP projects — CLI, routing, Spark ORM, Fuse views, auth, cache, queues, mail, Docker deployment
license: MIT
compatibility: opencode
metadata:
  audience: developers
  framework: sdf
---

## What I do

I help you build, scaffold, and maintain SDF PHP framework projects. I know the full stack: CLI generators, routing, Spark ORM models/migrations, Fuse templates, session/JWT auth, PSR-16 cache, mail, queues, events, schema builder, and Docker deployment.

Invoke me with `/project-sdf` followed by what you need.

## When to use me

| Scenario | What I'll do |
|----------|-------------|
| Scaffold a new feature | Generate controller, model, migration, view, routes, and test |
| Fix a bug | Diagnose the issue, suggest or apply the fix, run tests |
| Add an API endpoint | Create controller + route + validation + test |
| Database changes | Generate and run migrations, update models |
| Docker/deploy | Review compose.yaml, Caddyfile, Dockerfile, suggest improvements |
| Code review | Inspect changed files with security in mind, enforce SDF conventions |
| Run the app | Start dev server, run tests, check code style |
| Install the skill | `php sdf/cli agent install [local|global]` |

## Quick start
```bash
composer install
php sdf/cli serve -p 8000                # dev server at localhost:8000
php sdf/cli serve -p 8000 --live         # with auto-reload
```

## Common workflows

### Scaffold a full CRUD
```bash
php sdf/cli g model Post
php sdf/cli g migration create_posts_table
php sdf/cli g controller PostController
php sdf/cli g test PostControllerTest --type=controller
```
Then add routes in `routes.php`, fill in controller methods, define the migration schema with `Schema::create()`, and add Fuse views in `app/views/`.

### Add auth to a route
```php
Router::add('/dashboard', 'DashboardController@index')->middleware('auth');
```
Guards: `SessionGuard` (session-based), `JwtGuard` (HS256 JWT with refresh tokens).

### Fix code style + run checks
```bash
php sdf/cli format --dry-run -v          # preview style issues
php sdf/cli fmt                          # auto-fix
vendor/bin/phpunit                       # run tests
composer analyze                         # PHPStan level 5
```

### Spin up full stack
```bash
docker compose up -d    # FrankenPHP + MySQL 8.4 + Redis 7
```

## CLI reference
```bash
php sdf/cli g controller Home            # generate a controller
php sdf/cli g model Post                 # generate a model
php sdf/cli g migration create_posts_table
php sdf/cli g test HomeController --type=controller --namespace=App\\Controllers\\User
php sdf/cli g middleware Auth
php sdf/cli g guard Api
php sdf/cli g seeder UserSeeder
php sdf/cli db migrate                   # run pending migrations
php sdf/cli db rollback                  # rollback last batch
php sdf/cli db seed                      # run all seeders
php sdf/cli db reset                     # rollback all + migrate + seed
php sdf/cli cache clear                  # flush all caches
php sdf/cli format --dry-run -v          # check code style
php sdf/cli swagger generate             # generate OpenAPI spec
php sdf/cli agent install                # install this skill locally
php sdf/cli agent install global         # install this skill globally
```

## Project structure
```
├── app/
│   ├── config/          # PHP/JSON config files → $config['key'] = [...]
│   ├── controllers/     # your controllers
│   ├── handlers/        # error handlers (eh_pathNotFound, etc.)
│   ├── models/          # your models
│   └── views/           # Fuse templates (.fuse.php)
├── sdf/                 # framework core (don't touch)
├── public/              # static assets
├── storage/             # logs, cache, uploads
├── tests/
├── vendor/
├── .env                 # APP_KEY, DB_*, etc.
├── index.php            # entry point
├── routes.php           # route definitions
├── Dockerfile           # multi-stage (base/dev/production)
├── compose.yaml         # app + MySQL 8.4 + Redis 7
└── Caddyfile            # FrankenPHP/Caddy config
```

## Routing
```php
Router::add('/', function() { return 'Hello'; });
Router::add('/users', '\App\Controllers\UserController@index');
Router::add('/post/{id}', '\App\Controllers\PostController@show');
Router::add('/user/{uuid}', 'UserController@show');
Router::add('/login', 'AuthController@login', 'GET')->name('login');
Router::add('/dashboard', 'DashboardController@index')->middleware('auth');
```
Available placeholders: `{id}`, `{uuid}`, `{uuid_simple}`, `{slug}`, `{date}`, `{all}`, `{hex}`, `{alpha}`, `{alnum}`, `{word}`, `{segment}`, `{file}`, `{bool}`, `{time}`, `{datetime}`, `{url}`, `{num}`.

## Controllers
```php
namespace App\Controllers;
class PostController {
    public function index() { /* list */ }
    public function show(int $id) { /* single */ }
    public function store() { /* create */ }
    public function update(int $id) { /* update */ }
    public function destroy(int $id) { /* delete */ }
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
$posts = Spark::table('users')->where('age', '>', 18)->orderBy('name')->paginate(15);

// Mass assignment protection
class Post extends \SDF\Spark\Model {
    protected string $table = 'posts';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = false;
    protected array $fillable = ['title', 'content', 'published_at'];
    // or: protected array $guarded = ['id', 'is_admin'];

    public function author() { return $this->belongsTo(User::class, 'user_id'); }
    public function tags() { return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id'); }
}

$post = Post::find(1);
$post->title = 'Updated';
$post->save();
```

## Authentication
- **SessionGuard** — session-based login. Configure in `app/config/auth.php`
- **JwtGuard** — stateless HS256 JWT with refresh tokens. Min 32-byte key
```php
Router::add('/dashboard', 'DashboardController@index')->middleware('auth');
```
Middleware stack (applied in order): `CsrfMiddleware`, `CorsMiddleware`, `RateLimitMiddleware` (60 req/min), `AuthMiddleware`.

## Cache (PSR-16)
```php
use SDF\Cache\Cache;
Cache::set('key', 'value', 3600);
$value = Cache::get('key', 'default');
Cache::delete('key');
Cache::clear();
```
Drivers: `file` (default), `redis`, `memcached`. Set in `app/config/cache.php`.

## Validation
```php
$v = new \SDF\Validation\Validator($data, [
    'email' => 'required|email',
    'name'  => 'required|min:3|max:255',
    'age'   => 'numeric|between:18,99',
]);
if ($v->fails()) { $errors = $v->errors(); }
```
19 rules: required, email, min, max, between, numeric, integer, string, boolean, array, alpha, alpha_num, url, in, confirmed, same, different, regex, nullable.

## Components quick ref

| Component | Key class / method |
|-----------|-------------------|
| Encryption | `Encrypter::encrypt($text)` / `decrypt($cipher)` — AES-256-CBC + HMAC-SHA256 |
| PSR-7 HTTP | `ServerRequest`, `Response` — immutable (`with*` returns new instance) |
| Storage | `Storage::disk('local')->put('file.txt', 'content')` — drivers: local, s3 |
| Mail | `Mail::send($to, $subject, $body)` — via `NativeMailer` or SMTP |
| Queues | `Queue::dispatch(Job::class, $payload)` — drivers: `database`, `redis` |
| Events | `EventDispatcher::dispatch(new UserRegistered($user))` — PSR-14 |
| Logging | `Logger::info('msg')`, `Logger::error('msg', $context)` — PSR-3 |
| Schema | `Schema::create('table', fn($t) => $t->increments('id')...)` |
| Localization | `__('messages.welcome')` — language files in `app/lang/` |

## Deployment
```bash
docker compose up -d    # FrankenPHP + MySQL 8.4 + Redis 7
```
- `Dockerfile` multi-stage: `base` (composer deps), `dev` (xdebug), `production` (OPcache)
- Set `SDF_ENV=production` for production mode
- Static files served from `public/` (Caddy in Docker, dev server in dev mode)
- Caddyfile includes security headers, gzip, forbidden paths

## Troubleshooting
- **500 error / blank page**: Check `storage/logs/` for framework logs. Enable `SDF_ENV=development` for detailed errors
- **419 page expired**: CSRF token mismatch — ensure `X-CSRF-TOKEN` header or `_token` POST field is sent
- **429 too many requests**: Rate limit hit (default 60 req/min per IP+route). Wait for `Retry-After` header
- **Cache not updating**: Run `php sdf/cli cache clear` to flush all caches. Or set `debug = true` in router config to disable route cache
- **Migration fails**: Run `php sdf/cli db reset` to rollback all + re-migrate. Check `database.php` config for correct credentials
- **Docker connection refused**: Containers may still be starting. Run `docker compose logs` to check each service
