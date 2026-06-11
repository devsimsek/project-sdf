# Tutorial: Building a REST API

Build a fully working JSON REST API for a `products` resource using SDF routes, controllers, Spark ORM, and the Cache facade.

---

## 1. Database Migration

```bash
php sdf/cli g migration create_products_table
```

Edit the generated file in `app/migrations/`:

```php
<?php

class create_products_table_20240510000001
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(200) NOT NULL,
                price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                stock       INT UNSIGNED NOT NULL DEFAULT 0,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS products");
    }
}
```

```bash
php sdf/cli db migrate
```

---

## 2. Spark Model

```bash
php sdf/cli g model Product
```

`app/models/Product.php`:

```php
<?php

use SDF\Spark\Model;

class Product extends Model
{
    protected static string $table = 'products';
}
```

---

## 3. Routes

`app/config/routes.php`:

```php
<?php

$config['/api/products']        = ['api/ProductController/index',   'GET'];
$config['/api/products/{id}']   = ['api/ProductController/show',    'GET'];
$config['/api/products']        = ['api/ProductController/store',   'POST'];
$config['/api/products/{id}']   = ['api/ProductController/update',  'PUT'];
$config['/api/products/{id}']   = ['api/ProductController/destroy', 'DELETE'];
```

---

## 4. Controller

```bash
php sdf/cli g controller api/ProductController
```

`app/controllers/api/ProductController.php`:

```php
<?php

use SDF\Cache\Cache;
use SDF\Controller;

class ProductController extends Controller
{
    /** GET /api/products */
    public function index(): void
    {
        $products = Cache::remember('api.products.all', 300, function () {
            return Product::all();
        });
        $this->response->json($products);
    }

    /** GET /api/products/{id} */
    public function show(int $id): void
    {
        $product = Cache::remember("api.products.$id", 300, function () use ($id) {
            $rows = Product::query()->where('id', '=', $id)->get();
            return $rows[0] ?? null;
        });

        if (!$product) {
            $this->response->status(404)->json(['error' => 'Product not found']);
            return;
        }
        $this->response->json($product);
    }

    /** POST /api/products */
    public function store(): void
    {
        $body = $this->request->body();

        if (empty($body['name']) || !isset($body['price'])) {
            $this->response->status(422)->json([
                'error'  => 'Validation failed',
                'fields' => ['name' => 'required', 'price' => 'required'],
            ]);
            return;
        }

        Product::query()->insert([
            'name'  => $body['name'],
            'price' => (float) $body['price'],
            'stock' => (int) ($body['stock'] ?? 0),
        ]);

        Cache::forget('api.products.all');
        $this->response->status(201)->json(['created' => true]);
    }

    /** PUT /api/products/{id} */
    public function update(int $id): void
    {
        $rows = Product::query()->where('id', '=', $id)->get();
        if (empty($rows)) {
            $this->response->status(404)->json(['error' => 'Product not found']);
            return;
        }

        $body = $this->request->body();
        $pdo  = \SDF\Spark::pdo();
        $stmt = $pdo->prepare(
            'UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?'
        );
        $stmt->execute([
            $body['name']  ?? $rows[0]['name'],
            $body['price'] ?? $rows[0]['price'],
            $body['stock'] ?? $rows[0]['stock'],
            $id,
        ]);

        Cache::forget('api.products.all');
        Cache::forget("api.products.$id");
        $this->response->json(['updated' => true]);
    }

    /** DELETE /api/products/{id} */
    public function destroy(int $id): void
    {
        $pdo  = \SDF\Spark::pdo();
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);

        Cache::forget('api.products.all');
        Cache::forget("api.products.$id");
        $this->response->status(204)->json([]);
    }
}
```

> **PSR-7 alternative:** Use `\SDF\Http\ServerRequest::fromGlobals()` to get a PSR-7 `ServerRequestInterface`
> and access parsed JSON body via `$request->getParsedBody()`. The legacy
> `$this->request->body()` remains fully supported for BC.

---

## 5. Test with curl

```bash
# List all
curl http://localhost:8080/api/products

# Create
curl -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Widget","price":9.99,"stock":100}'

# Show
curl http://localhost:8080/api/products/1

# Update
curl -X PUT http://localhost:8080/api/products/1 \
  -H "Content-Type: application/json" \
  -d '{"price":7.99}'

# Delete
curl -X DELETE http://localhost:8080/api/products/1
```

---

## New Feature Integration

**CORS middleware** — Register `CorsMiddleware` on your API route group to allow cross-origin requests:
```php
Router::middleware(\SDF\Middleware\CorsMiddleware::class);
```

**Rate limiting** — Protect API endpoints from abuse with `RateLimitMiddleware`:
```php
Router::middleware(\SDF\Middleware\RateLimitMiddleware::class);
```

**Env-based config** — Store API keys in `.env` instead of hardcoding:
```php
$apiKey = \SDF\Env::get('API_KEY');
```

---

## What You Learned

- Defining RESTful routes with HTTP method constraints
- Generating models, controllers, and migrations via CLI
- Using `Spark::pdo()` for raw UPDATE/DELETE queries
- Returning proper HTTP status codes with `$this->response->status()`
- Caching GET responses with `Cache::remember()` and invalidating on mutation
