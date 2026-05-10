# Fuse View Engine

Fuse is the SDF template engine. **v2.0.0 removes `eval()`** — templates compile to PHP files cached on disk. Secure, fast, zero-overhead after first render.

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

## Variable Interpolation

`{{ $var }}` — escaped via `htmlspecialchars`. Safe by default.

```html
<p>Welcome, {{ $user['name'] }}!</p>
<p>Email: {{ $user['email'] }}</p>
```

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

### `@var` — Inline PHP assignment

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
- Subsequent renders: compiled file used directly — no parsing overhead
- Cache invalidated automatically when source file `mtime` changes

## Supported Extensions

Fuse resolves view files in this order: `.php` → `.phtml` → `.fuse`

```php
$this->fuse->render('home');
// looks for: app/views/home.php
//            app/views/home.phtml
//            app/views/home.fuse
```
