# Routes

Routes map URLs to controller methods. Defined in `app/config/routes.php`.

## Basic Route

```php
<?php
// calls Home::index()
$config['/'] = 'Home';

// calls Home::about()
$config['/about'] = 'Home/about';
```

## HTTP Method Constraints

```php
<?php
$config['/api/users']        = ['Api/UserController/index', 'GET'];
$config['/api/users']        = ['Api/UserController/store', 'POST'];
$config['/api/users/{id}']   = ['Api/UserController/show',  'GET'];
$config['/api/users/{id}']   = ['Api/UserController/update','PUT'];
$config['/api/users/{id}']   = ['Api/UserController/destroy','DELETE'];
```

## Dynamic Segments

| Placeholder | Matches |
|---|---|
| `{id}` | Numeric (`[0-9]+`) |
| `{num}` | Numeric (alias) |
| `{url}` | Alphanumeric (`[0-9a-zA-Z]+`) |
| `{all}` | Everything (`(.*)`) |
| `{uuid}` | RFC 4122 UUID (v1–v5) |
| `{uuid_simple}` | UUID without dashes (32 hex chars) |
| `{hex}` | Hex digits (`[0-9a-fA-F]+`) |
| `{alpha}` | Letters only (`[A-Za-z]+`) |
| `{alnum}` | Letters and digits (`[A-Za-z0-9]+`) |
| `{word}` | Word chars + hyphen (`[A-Za-z0-9_\\-]+`) |
| `{slug}` | Lowercase slug (`[a-z0-9]+(?:-[a-z0-9]+)*`) |
| `{segment}` | Single path segment (no slashes) |
| `{file}` | Filename with extension |
| `{bool}` | `0`, `1`, `true`, `false` |
| `{date}` | `YYYY-MM-DD` |
| `{time}` | `HH:MM:SS` |
| `{datetime}` | ISO 8601 datetime |

```php
<?php
$config['/post/{id}']              = 'Blog/show';      // /post/42
$config['/category/{url}']         = 'Blog/category';  // /category/tech
$config['/files/{all}']            = 'File/serve';     // /files/2024/report.pdf
```

## Real-World Blog Example

```php
<?php
// app/config/routes.php

$config['/']                   = 'Blog/index';
$config['/post/{id}']          = ['Blog/show',   'GET'];
$config['/post']               = ['Blog/create', 'POST'];
$config['/post/{id}/edit']     = ['Blog/edit',   'GET'];
$config['/post/{id}']          = ['Blog/update', 'PUT'];
$config['/post/{id}']          = ['Blog/delete', 'DELETE'];
$config['/tag/{url}']          = 'Blog/tag';
$config['/search']             = ['Blog/search', 'GET'];
```

Matching controller:

```php
<?php

class Blog extends SDF\Controller
{
    public function index(): void
    {
        $posts = Post::all();
        $this->fuse->with(compact('posts'))->render('blog/index');
    }

    public function show(int $id): void
    {
        $post = Post::query()->where('id', '=', $id)->get()[0] ?? null;
        if (!$post) {
            $this->response->status(404)->text('Post not found');
            return;
        }
        $this->fuse->with(compact('post'))->render('blog/show');
    }

    public function create(): void
    {
        $data = $this->request->body();
        Post::query()->insert([
            'title'   => $data['title'],
            'content' => $data['content'],
        ]);
        $this->response->status(201)->json(['created' => true]);
    }
}
```

## Route Caching (v2.0.0)

Routes compile to `/tmp/sdf_routes.cache` on first request.
Disable caching for development by setting `debug = true` in router config:

```php
<?php
// app/config/routes.php (top of file)
SDF\Router::setRConfig(['debug' => true]);
```
