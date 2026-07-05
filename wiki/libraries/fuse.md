# Fuse View Engine

Fuse is the SDF template engine. **v2.0.0 removes `eval()`** - templates compile to PHP files cached on disk. Secure, fast, zero-overhead after first render.

## Rendering a View

From a controller:

```php
$this->fuse->render('home');                   // renders app/views/home.php
$this->fuse->render('dashboard/index');        // subdirectory
$this->fuse->with(['user' => $user])->render('profile');
```

## Passing Data

```php
// Single key
$this->fuse->with('title', 'My Blog')->render('blog/index');

// Array merge
$this->fuse
    ->with(['posts' => $posts, 'total' => count($posts)])
    ->render('blog/index');
```

In the view, data is available as extracted PHP variables:

```html
<h1>{{ $title }}</h1>
<p>{{ $total }} posts found.</p>
```

### Safe variable extraction (v2.3.1+)

Both `Fuse::render()` and `Loader::view()` extract view data with `EXTR_SKIP` instead of the default `EXTR_OVERWRITE`. This means **view data cannot overwrite the framework's internal local variables** (`$cacheFile`, `$cacheDir`, `$path`, `$viewFile`, `$viewName`, `$directory`, `$useFuse`). Without this guard, passing user-controlled keys like `['cacheFile' => '/etc/passwd']` into a view would let an attacker hijack the `require` path (LFI/RCE).

> Safe pattern: `Fuse::with($_REQUEST)->render(...)` is now safe; previously it was not. You should still prefer `with(['specific' => $value])` over passing raw superglobals.

## Variable Interpolation

`{{ $var }}` - escaped via `htmlspecialchars`. Safe by default.

```html
<p>Welcome, {{ $user['name'] }}!</p>
<p>Email: {{ $user['email'] }}</p>
```

> As of v2.3.1, `{{ $var }}` compiles to `htmlspecialchars($var, ENT_QUOTES)`. Note this omits `ENT_SUBSTITUTE` and an explicit `'UTF-8'` charset argument - tracked for a future hardening release. For now, ensure your app serves UTF-8 to avoid edge cases with invalid byte sequences.

## Directives

### `@If / @ElseIf / @Else / @endIf`

```html
@If($user['role'] === 'admin')
  <a href="/admin">Admin Panel</a>
@ElseIf($user['role'] === 'editor')
  <a href="/editor">Editor Panel</a>
@Else
  <p>Welcome, {{ $user['name'] }}</p>
@endIf
```

### `@Foreach / @endForeach`

```html
<ul>
@Foreach($posts as $post)
  <li>
    <a href="/post/{{ $post['id'] }}">{{ $post['title'] }}</a>
    <small>by {{ $post['author'] }}</small>
  </li>
@endForeach
</ul>
```

### `@For / @endFor`

```html
@For($i = 1; $i <= 5; $i++)
  <span class="star">★</span>
@endFor
```

### `@While / @endWhile`

```html
@var $n = 0;
@While($n < 3)
  <p>Item {{ $n }}</p>
  @var $n++;
@endWhile
```

### `@var` - Inline PHP assignment

```html
@var $greeting = 'Hello, ' . $user['name'] . '!';
<h1>{{ $greeting }}</h1>
```

## Real-World Template Example

`app/views/blog/index.php`:

```html
<!doctype html>
<html lang="en">
<head>
  <title>{{ $title ?? 'Blog' }}</title>
</head>
<body>
<header>
  @If(isset($user))
    <p>Logged in as {{ $user['name'] }}</p>
  @Else
    <a href="/login">Login</a>
  @endIf
</header>

<main>
  @If(empty($posts))
    <p>No posts yet.</p>
  @Else
    @Foreach($posts as $post)
      <article>
        <h2><a href="/post/{{ $post['id'] }}">{{ $post['title'] }}</a></h2>
        <p>{{ $post['excerpt'] }}</p>
        <time>{{ $post['created_at'] }}</time>
      </article>
    @endForeach
  @endIf
</main>
</body>
</html>
```

## Cache Behaviour (v2.0.0)

- First render: template compiled → written to `SDF_APP_CACHE/views/` (or `/tmp/fuse_cache/`)
- Subsequent renders: compiled file used directly - no parsing overhead
- Cache invalidated automatically when source file `mtime` changes

## Supported Extensions

Fuse resolves view files in this order: `.php` → `.phtml` → `.fuse`

```php
$this->fuse->render('home');
// looks for: app/views/home.php
//            app/views/home.phtml
//            app/views/home.fuse
```

## Template Inheritance (v2.3+)

Fuse supports Blade-style template inheritance:

### Layout
```php
{{-- layouts/app.php --}}
<!DOCTYPE html>
<html>
<head><title>@yield('title', 'Default')</title></head>
<body>
  @yield('content')
</body>
</html>
```

### Child template
```php
@extends('layouts/app')
@section('title', 'My Page')
@section('content')
  <h1>Hello</h1>
@endsection
```

### Directives
- `@extends('view')` — inherit from a parent layout
- `@section('name')` / `@endsection` — define a content block
- `@yield('name', 'default')` — render a section from child
- `@include('view')` — include another view inline
