# Core Internals

## Boot Sequence

```
index.php
  └─ sdf/__init.php
       ├─ Define constants (SDF_VERSION, SDF_ROOT, SDF_APP, ...)
       ├─ Core::coreLoadConfigurations()   ← config cache hit? return. else load + cache.
       ├─ Benchmark::start()
       ├─ Router::setRConfig()
       ├─ Router::add() × N                 ← register routes from routes.php
       └─ Router::ignite()                  ← route cache hit? skip prep. else compile + cache.
            └─ Controller::method($params)
```

## Core Methods

### `coreLoadConfigurations(string $dir = 'config')`

Scans `app/config/` for `.php` and `.json` files. Merges into `Core::$config`. Result cached to `/tmp/sdf_config.cache`.

```php
// Internal usage — called automatically at boot.
// Access config in controllers:
$mailHost = $this->getConfig('mail', 'host');
```

### `coreGetConfig(string $file, string $key = null)`

```php
$all  = Core::coreGetConfig('app');           // full app config array
$name = Core::coreGetConfig('app', 'name');   // single value
```

### `coreScanDirectory(string $path, string $pattern = '.php')`

Returns filenames matching `$pattern` from a directory. Used internally for config and migration loading.

### `coreTriggerError(string $handler, ...)`

Calls a user-defined error handler function (defined in `app/helpers/`). See [Handlers](../app/handlers.md).

## Router

### Adding Routes (internal)

```php
// Called automatically by routes.php via __init.php
Router::add('/post/{id}', 'Blog/show', 'GET');
```

### `Router::ignite(string $basepath, string $controllerDir)`

Main dispatch method. On first call: prepares regex expressions and caches to `/tmp/sdf_routes.cache`.
On subsequent calls: loads compiled cache directly (no regex recompilation).

### Route Cache Invalidation

```bash
rm /tmp/sdf_routes.cache   # force rebuild
```

Or enable debug mode in `app/config/routes.php`:

```php
SDF\Router::setRConfig(['debug' => true]);
```

## Loader

Accessed via `$this->load` in controllers.

```php
$this->load->view('dashboard/index');        // render a view
$this->load->model('User');                  // require app/models/User.php
$this->load->helper('string_helpers');       // require app/helpers/string_helpers.php
$this->load->library('CsvExporter');         // require app/libraries/CsvExporter.php
$this->load->config('mail');                 // load app/config/mail.php
```

## Controller Base

All controllers extend `SDF\Controller`. Properties auto-injected:

```php
class Example extends SDF\Controller
{
    public function demo(): void
    {
        // Request
        $id   = $this->request->get('id');
        $body = $this->request->json();       // parsed JSON body

        // Response
        $this->response->json(['ok' => true], 200);

        // Config
        $appName = $this->getConfig('app', 'name');

        // Fuse
        $this->fuse->with(compact('id'))->render('demo');

        // Loader
        $this->load->model('Product');
    }
}
```

## Application Scopes (v2.0.0)

`SDF\Scope` defines constants for context classification:

| Constant | Value | Usage |
|---|---|---|
| `Scope::Controller` | `'controller'` | Controller logic context |
| `Scope::Helper` | `'helper'` | Helper function context |
| `Scope::Global` | `'global'` | Global app context |
| `Scope::System` | `'system'` | Core framework context |
| `Scope::View` | `'view'` | View/template context |

```php
use SDF\Scope;

$context = Scope::Controller;   // 'controller'
```
