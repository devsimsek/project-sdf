# Project SDF — Agent Skill

## Quick start
```bash
composer install
vendor/bin/phpunit --testdox
vendor/bin/phpunit --filter=CoreSurface --testdox
composer analyze
composer cs:fix
composer cs:check
```

## CLI
```bash
php sdf/cli serve -p 8000              # dev server (auto-detect frankenphp or PHP built-in)
php sdf/cli serve -p 8000 --live       # with live-reload watcher
php sdf/cli g controller Home          # generate component
php sdf/cli g test UserController --type=controller --namespace=App\\Controllers\\User
php sdf/cli db migrate                 # run pending migrations
php sdf/cli cache clear                # clear framework caches
php sdf/cli format --dry-run -v        # run cs-fixer via CLI
```

## Architecture
- **Entry**: `index.php` defines constants (`SDF`, `USE_FUSE`, `SDF_ENV`, etc.) then requires `sdf/__init.php`.
- **PSR-4**: `SDF\` → `sdf/core/`, `Tests\` → `tests/`.
- **CoreUtilities trait** on `SDF\Core` holds shared static state (`$classes`, `$config`).
- **Config**: `app/config/*.php` or `app/config/*.json`; files declare `$config['key'] = [...]`. Cached to `sys_get_temp_dir()/sdf_config.cache`.
- **No DI container** — classes use `Core::coreLoadClass()` singleton-style loading.
- **Router cache**: `sys_get_temp_dir()/sdf_routes.cache`.
- **Cache facade**: `SDF\Cache\Cache` — PSR-16 proxy. Driver (`file`/`redis`/`memcached`) set in `app/config/cache.php`.
- **Fuse view engine**: compiles templates via Cache facade; raw files also written to `sys_get_temp_dir()/fuse_cache/` for `require`.

## Testing quirks
- PHPUnit 10, bootstrap `tests/bootstrap.php` defines framework constants + requires all core files.
- Tests use `ReflectionClass` extensively to access private static properties (no public getters/setters). See `CoreSurfaceTest.php`, `QueryBuilderTest.php` for patterns.
- **Must reset static state** in `setUp`/`tearDown` — `Core::$config`, `Router::$routes/$middlewares/$config` are shared.
- CLI generator tests (`CLIGeneratorTest.php`) run in temp dirs.
- SQLite in-memory connections for integration-style tests (`Spark::connect('sqlite::memory:')`).
- Use `$this->getProperty()` / `$this->getStaticProperty()` helpers from `CoreSurfaceTest` base.

## Key constraints
- PHP **8.2+** required (checked in `sdf/__init.php`).
- `composer.lock` is gitignored.
- `.php-cs-fixer.dist.php` only targets `sdf/` and `app/` dirs.
- PHPStan level 5, excludes `tests/`, bootstraps framework constants via `phpstan-bootstrap.php`.
- Error handlers in `app/handlers/errors.php` — functions `eh_pathNotFound`, `eh_methodNotAllowed`, `eh_errorHandler`.
- Static files only served in development mode via `SDF_STATIC_MIMES` constant.

## Code conventions
- No `die`/`exit` in kernel/core (allowed in `sdf/cli` standalone CLI script).
- File header docblocks with `@package`, `@subpackage`, `@file`, `@version`, `@author`, `@copyright`, `@license`, `@link`, `@since`, `@filesource`.
- Class docblocks; method docblocks with `@param`, `@return`.
- **No inline `//` comments in method bodies** — only docblocks.
- PSR-7 implementations must be immutable (`with*` returns new instance); live alongside legacy mutable classes for BC.
- Session `session_start()` deferred to first `get()`/`set()`.
- Router stores static routes in separate hash map for O(1) exact-path matching.
- Swagger routes auto-registered only in `development` environment.
- `zircote/swagger-php` moved to `require-dev` — not loaded in production.
- Configuration: `app/config/*.php` files set `$config['key'] = [...]`.
- Run `composer dump-autoload -o --apcu` after adding new classes.

## Router
- `add(string $expression, string|callable $controller, string $method = "any")`.
- Supports `@` syntax for namespaced controllers: `\SDF\Swagger\SwaggerController@spec`.
- Supports callable controllers: `function() { ... }`.
- Supports `/` syntax: `Dir/Controller/method`.
- Static routes (no `{param}` tokens) stored in `$staticRoutes` for O(1) lookup.
- Routes cached to `sys_get_temp_dir()/sdf_routes.cache` via `serialize`/`unserialize` with `allowed_classes=false`.

## Auth
- Guards: `SessionGuard` (session-based), `JwtGuard` (HS256 stateless JWT) with refresh token support.
- Auth middleware: `AuthMiddleware` — rejects with 401 if no authenticated user.
- Config: `app/config/auth.php`.

## Middleware
- `CsrfMiddleware` — per-session CSRF validation, 419 on mismatch. Token via `X-CSRF-TOKEN` header or `_token` POST field.
- `CorsMiddleware` — configurable origins/methods/headers, OPTIONS → 204. Wildcard+credentials echoes request Origin + `Vary: Origin`.
- `RateLimitMiddleware` — per-IP/route via Cache, 429 with `Retry-After`. Malformed entries rebuilt.
- `AuthMiddleware` — requires authenticated session/token.

## Key implementations
- `sdf/core/Env.php`: `.env` loader (KV parsing, quotes, `${VAR}` placeholders, circular reference guard).
- `sdf/core/helpers.php`: Global `env()`, `csrf_token()`, `csrf_field()` helpers.
- `sdf/core/Encryption/Encrypter.php`: AES-256-CBC + HMAC-SHA256 encrypt-then-MAC.
- `sdf/core/Validation/Validator.php`: 19 rules (required, email, min, max, between, numeric, integer, string, boolean, array, alpha, alpha_num, url, in, confirmed, same, different, regex, nullable), custom messages, aliases, custom rules.
- `sdf/core/Spark/Model.php`: ORM with `hasOne()`, `hasMany()`, `belongsTo()`, `belongsToMany()`.
- `sdf/core/Spark/Paginator.php`: Pagination result class.
- `sdf/core/Spark.php`: QueryBuilder with `select()`, `join()`, `paginate()`, `where()` LSB fix.

## Performance
- FrankenPHP `v1.11.2` (Homebrew) on PHP 8.5.3: ~10k req/sec on home route (full stack with Fuse view).
- Redis SCAN instead of KEYS for cache clear.
- RateLimitMiddleware stores raw arrays (not JSON strings) to avoid double-serialization.
- SwaggerController echos JSON directly (no decode/encode cycle).
- Config caching uses raw file (`sdf_config.cache`) to avoid circular dependency with Cache facade.
- DB connection fully lazy: `Spark::ensureConnected()` auto-inits from config on first query.
- Cache driver lazy-loaded in `resolveDriver()`.

## Git
- Only commit/amend/push when explicitly requested.
- Before committing, inspect `git status`, `git diff`, `git log --oneline -10`; stage only intended files.
- Write concise commit messages matching repo style.
- Never force-push, skip hooks, or use interactive `-i`.

When working on this codebase, always run tests before committing (`vendor/bin/phpunit --testdox`). If adding new classes, run `composer dump-autoload -o --apcu`.

## Wiki
Full documentation is at `wiki/`:
- `wiki/libraries/` — lib docs (auth, cache, csrf, cors, ratelimit, encryption, validation, session, flash, request, http, swagger, benchmark).
- `wiki/app/tutorials/` — tutorials (authentication, blog, docker-frankenphp).
- `wiki/sdf/` — CLI docs.
- `wiki/agentic_workflow/` — agentic development workflows.
