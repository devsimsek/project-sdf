# Tutorial: Blog Application

Full end-to-end blog: routes, Spark ORM, controllers, Fuse templates, Auth, Flash, Session, and Cache.

---

## 1. Migrations

```bash
php sdf/cli g migration create_posts_table
```

```php
<?php

class create_posts_table_20240510000003
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title      VARCHAR(255) NOT NULL,
                slug       VARCHAR(255) NOT NULL UNIQUE,
                excerpt    TEXT,
                content    LONGTEXT NOT NULL,
                status     ENUM('draft','published') DEFAULT 'draft',
                user_id    INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS posts");
    }
}
```

```bash
php sdf/cli db migrate
```

---

## 2. Post Model

`app/models/Post.php`:

```php
<?php

use SDF\Spark\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    public static function published(): array
    {
        return self::query()->where('status', '=', 'published')->get();
    }

    public static function findBySlug(string $slug): ?array
    {
        $rows = self::query()->where('slug', '=', $slug)->get();
        return $rows[0] ?? null;
    }
}
```

---

## 3. Routes

```php
<?php
// app/config/routes.php

use SDF\Auth\AuthMiddleware;
use SDF\Router;

// Public routes
$config['/']                = 'Blog/index';
$config['/post/{url}']      = ['Blog/show',   'GET'];

// Admin routes (protected)
Router::middleware(AuthMiddleware::class);
$config['/admin/post']      = ['Admin\PostController/create', 'GET'];
$config['/admin/post']      = ['Admin\PostController/store',  'POST'];
$config['/admin/post/{id}'] = ['Admin\PostController/edit',   'GET'];
$config['/admin/post/{id}'] = ['Admin\PostController/update', 'PUT'];
$config['/admin/post/{id}'] = ['Admin\PostController/destroy','DELETE'];
```

---

## 4. Public Blog Controller

`app/controllers/Blog.php`:

```php
<?php

use SDF\Cache\Cache;
use SDF\Controller;

class Blog extends Controller
{
    public function index(): void
    {
        $posts = Cache::remember('blog.published', 300, function () {
            return Post::published();
        });
        $this->fuse->with(compact('posts'))->render('blog/index');
    }

    public function show(string $slug): void
    {
        $post = Post::findBySlug($slug);
        if (!$post) {
            $this->response->status(404)->text('Post not found');
            return;
        }
        $this->fuse->with(compact('post'))->render('blog/show');
    }
}
```

---

## 5. Admin Post Controller

`app/controllers/Admin/PostController.php`:

```php
<?php

use SDF\Auth\Auth;
use SDF\Cache\Cache;
use SDF\Controller;
use SDF\Flash;

class PostController extends Controller
{
    public function create(): void
    {
        $this->fuse->render('admin/post/create');
    }

    public function store(): void
    {
        $body = $this->request->body() ?: $_POST;
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $body['title']));

        Post::query()->insert([
            'title'   => $body['title'],
            'slug'    => $slug,
            'excerpt' => $body['excerpt'] ?? '',
            'content' => $body['content'],
            'status'  => $body['status'] ?? 'draft',
            'user_id' => Auth::user()['id'],
        ]);

        Cache::forget('blog.published');
        Flash::set('success', 'Post created!');
        $this->response->redirect('/');
    }

    public function edit(int $id): void
    {
        $rows = Post::query()->where('id', '=', $id)->get();
        if (empty($rows)) {
            $this->response->status(404)->text('Not found');
            return;
        }
        $this->fuse->with(['post' => $rows[0]])->render('admin/post/edit');
    }

    public function update(int $id): void
    {
        $body = $this->request->body() ?: $_POST;
        $pdo  = \SDF\Spark::pdo();
        $stmt = $pdo->prepare(
            'UPDATE posts SET title=?, content=?, status=? WHERE id=?'
        );
        $stmt->execute([$body['title'], $body['content'], $body['status'], $id]);

        Cache::forget('blog.published');
        Flash::set('success', 'Post updated!');
        $this->response->json(['updated' => true]);
    }

    public function destroy(int $id): void
    {
        $pdo  = \SDF\Spark::pdo();
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$id]);

        Cache::forget('blog.published');
        Flash::set('info', 'Post deleted.');
        $this->response->status(204)->json([]);
    }
}
```

---

## 6. Fuse Templates

**`app/views/blog/index.php`**:

```html
<!doctype html>
<html lang="en">
<head><title>Blog</title></head>
<body>
<h1>Latest Posts</h1>

@If(empty($posts))
  <p>No posts published yet.</p>
@Else
  @Foreach($posts as $post)
    <article>
      <h2><a href="/post/{{ $post['slug'] }}">{{ $post['title'] }}</a></h2>
      <p>{{ $post['excerpt'] }}</p>
      <time>{{ $post['created_at'] }}</time>
    </article>
  @endForeach
@endIf
</body>
</html>
```

**`app/views/blog/show.php`**:

```html
<!doctype html>
<html lang="en">
<head><title>{{ $post['title'] }}</title></head>
<body>
  <article>
    <h1>{{ $post['title'] }}</h1>
    <time>{{ $post['created_at'] }}</time>
    <div><?= nl2br(htmlspecialchars($post['content'])) ?></div>
  </article>
  <a href="/">← Back</a>
</body>
</html>
```

---

## What You Learned

- Full MVC lifecycle in SDF
- Model helper methods (`published()`, `findBySlug()`)
- Slug generation from titles
- Admin vs public controller separation via subdirectories
- Fuse `@Foreach` + `@If` in real templates
- Caching the published listing with `Cache::remember()` and invalidating on mutation
- Using `Auth::user()` instead of `$_SESSION` superglobals
- Sending flash messages with `SDF\Flash` across redirects
