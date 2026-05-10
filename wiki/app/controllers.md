# Controllers

Controllers handle incoming HTTP requests. They live in `app/controllers/`.

## Basic Controller

```php
<?php

class Home extends SDF\Controller
{
    public function index(): void
    {
        $this->fuse->render('home');
    }
}
```

## Accessing Request Data

```php
<?php

class Auth extends SDF\Controller
{
    public function login(): void
    {
        $email    = $this->request->post('email');
        $password = $this->request->post('password');

        if (!$email || !$password) {
            $this->response->status(422)->json([
                'error' => 'email and password required'
            ]);
            return;
        }

        // ... auth logic
        $this->response->json(['token' => 'eyJ...']);
    }
}
```

## Returning JSON (REST API)

```php
<?php

class Api\UserController extends SDF\Controller
{
    public function index(): void
    {
        $users = User::all();
        $this->response->json($users);
    }

    public function show(int $id): void
    {
        $rows = User::query()->where('id', '=', $id)->get();
        if (empty($rows)) {
            $this->response->status(404)->json(['error' => 'Not found']);
            return;
        }
        $this->response->json($rows[0]);
    }

    public function store(): void
    {
        $body = $this->request->body();
        User::query()->insert([
            'name'  => $body['name'],
            'email' => $body['email'],
        ]);
        $this->response->status(201)->json(['created' => true]);
    }
}
```

## Rendering Views with Data

```php
<?php

class Dashboard extends SDF\Controller
{
    public function index(): void
    {
        $user   = $this->request->session('user');
        $orders = Order::query()->where('user_id', '=', $user['id'])->get();

        $this->fuse
            ->with('user', $user)
            ->with('orders', $orders)
            ->with('total', count($orders))
            ->render('dashboard/index');
    }
}
```

In `app/views/dashboard/index.php`:

```html
<h1>Welcome, {{ $user['name'] }}</h1>
<p>You have {{ $total }} orders.</p>

@Foreach($orders as $order)
  <div class="order">
    <span>{{ $order['id'] }}</span>
    <span>{{ $order['status'] }}</span>
  </div>
@endForeach
```

## Loading Helpers & Libraries

```php
<?php

class Reports extends SDF\Controller
{
    public function index(): void
    {
        $this->load->helper('date_helper');
        $this->load->library('CsvExporter');

        $data = Report::all();
        $csv  = new CsvExporter($data);
        $csv->download('report.csv');
    }
}
```

## Available Controller Properties

| Property | Type | Description |
|---|---|---|
| `$this->fuse` | `Fuse` | Render Fuse templates |
| `$this->request` | `Request` | Access GET/POST/headers/body |
| `$this->response` | `Response` | Send responses, set status |
| `$this->load` | `Loader` | Load views, helpers, models, libraries |

## Subdirectory Controllers

For `app/controllers/api/UserController.php`:

```php
<?php
// app/config/routes.php
$config['/api/users'] = ['api/UserController/index', 'GET'];
```

The CLI generator handles subdirectories automatically:

```bash
php sdf/cli g controller api/UserController
```
