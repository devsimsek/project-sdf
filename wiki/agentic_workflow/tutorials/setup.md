# Agentic Setup Guide

## Prerequisites

- PHP 8.2+
- Composer
- (Optional) FrankenPHP — auto-detected by `sdf/cli serve`

## Skill Integration

The [project-sdf skill](../skills/project-sdf.md) is the single source of truth for agents working on this codebase. Load it at the start of each session:

```bash
# In opencode, load the skill file
# The skill contains: commands, architecture, testing quirks, code conventions
```

## Agent Development Workflow

1. **Load the project-sdf skill** — provides full codebase context
2. **Explore** — use search tools to understand relevant files
3. **Plan** — outline changes before implementation
4. **Implement** — follow code conventions (no inline comments, docblocks required)
5. **Test** — run `vendor/bin/phpunit --testdox` before committing
6. **Lint** — run `composer analyze` (PHPStan) and `composer cs:check`
7. **Commit** — concise message matching repo style

## Directory Map

```
sdf/core/         → SDF\ namespace (framework core)
sdf/core/Cache/   → PSR-16 Cache drivers
sdf/core/Auth/    → Guards (SessionGuard, JwtGuard)
sdf/core/Middleware/ → Csrf, Cors, RateLimit, Auth
sdf/core/Encryption/ → Encrypter
sdf/core/Validation/ → Validator
sdf/core/Spark/   → ORM (Model, Paginator, Pool)
sdf/core/Http/    → PSR-7 implementations
sdf/core/Swagger/ → OpenAPI generator + controller
app/config/       → Configuration files
app/controllers/  → Application controllers
app/views/        → Fuse/PHP view templates
tests/            → PHPUnit tests (Tests\ namespace)
wiki/             → Documentation
```

## Key Rules for Agents

1. Always run tests before committing (`vendor/bin/phpunit --testdox`)
2. No `die`/`exit` in kernel/core — allowed only in `sdf/cli`
3. No inline `//` comments in method bodies — docblocks only
4. File headers must include full docblock with `@package`, `@author`, etc.
5. Reset static state in test `setUp`/`tearDown` (`Core::$config`, `Router::$routes`)
6. For new classes, run `composer dump-autoload -o --apcu`
7. Use `uniqid()` or reflection for test isolation (shared static state)
