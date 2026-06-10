# OpenAPI / Swagger Documentation

SDF integrates with **zircote/swagger-php** to generate OpenAPI 3.x specs from PHP 8 attributes, and serves Swagger UI for interactive documentation.

---

## Annotating Controllers

Use `#[OA\...]` attributes on your controller methods:

```php
<?php

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", title: "Products API")]
class ProductController extends SDF\Controller
{
    #[OA\Get(
        path: "/api/products",
        summary: "List all products",
        tags: ["Products"],
        responses: [
            new OA\Response(response: 200, description: "Product list"),
        ],
    )]
    public function index(): void
    {
        $this->response->json(Product::all());
    }

    #[OA\Post(
        path: "/api/products",
        summary: "Create a product",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ],
            ),
        ),
        tags: ["Products"],
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 422, description: "Validation error"),
        ],
    )]
    public function store(): void
    {
        // ...
    }
}
```

## Endpoints

| Route | Description |
|---|---|
| `GET /api/openapi.json` | OpenAPI 3.x spec (JSON) |
| `GET /api/docs` | Swagger UI (CDN) |

> These routes are automatically registered in `development` environment when `\SDF\Swagger\SwaggerController` is available.

## CLI Command

Generate the spec offline:

```bash
php sdf/cli swagger generate
# Writes openapi.json to the current directory

php sdf/cli swagger generate path/to/openapi.json
# Custom output path
```

## Generator API

```php
use SDF\Swagger\SwaggerGenerator;

$generator = new SwaggerGenerator(
    title: 'My API',
    apiVersion: '2.0.0',
    serverUrl: 'https://api.example.com',
    description: 'Optional description',
);

// As JSON string
$json = $generator->generate();

// As array
$array = $generator->generateArray();

// Add extra scan paths
$generator->addPaths('/path/to/models', '/path/to/libs');

// Override OpenAPI version
$generator->setSpecVersion('3.1.0');
```

## Requirements

- `zircote/swagger-php: ^4.0` (in `require-dev`)
- PHP 8.1+ (for attributes)
