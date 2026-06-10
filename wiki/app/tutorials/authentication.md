# Tutorial: User Authentication

Implement session-based login, route protection with `AuthMiddleware`, and user registration using the built-in Auth library.

## 1. Users Migration

```bash
php sdf/cli g migration create_users_table
```

```php
<?php

class create_users_table_20240510000002
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name          VARCHAR(120) NOT NULL,
                email         VARCHAR(255) NOT NULL UNIQUE,
                password      VARCHAR(255) NOT NULL,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS users");
    }
}
```

```bash
php sdf/cli db migrate
```

## 2. User Model

`app/models/User.php`:

```php
<?php

use SDF\Spark\Model;

class User extends Model
{
    protected static string $table = 'users';
}
```

## 3. Auth Controller

`app/controllers/AuthController.php`:

```php
<?php

use SDF\Auth\Auth;
use SDF\Controller;

class AuthController extends Controller
{
    /** GET /login — show form */
    public function loginForm(): void
    {
        $this->fuse->render('auth/login');
    }

    /** POST /login — authenticate */
    public function login(): void
    {
        $email    = $this->request->post('email');
        $password = $this->request->post('password');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $this->response->redirect('/dashboard');
        } else {
            $this->fuse
                ->with('error', 'Invalid email or password.')
                ->render('auth/login');
        }
    }

    /** GET /logout */
    public function logout(): void
    {
        Auth::logout();
        $this->response->redirect('/login');
    }

    /** POST /register */
    public function register(): void
    {
        $body = $this->request->body() ?: $_POST;

        if (empty($body['name']) || empty($body['email']) || empty($body['password'])) {
            $this->response->status(422)->json(['error' => 'All fields required']);
            return;
        }

        User::query()->insert([
            'name'     => $body['name'],
            'email'    => $body['email'],
            'password' => password_hash($body['password'], PASSWORD_BCRYPT),
        ]);

        $this->response->redirect('/login');
    }
}
```

## 4. Protected Controller with AuthMiddleware

`app/controllers/Dashboard.php`:

```php
<?php

use SDF\Auth\Auth;
use SDF\Controller;

class Dashboard extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $this->fuse->with(compact('user'))->render('dashboard/index');
    }
}
```

## 5. AuthMiddleware (built-in)

The framework ships with `SDF\Auth\AuthMiddleware`. It checks the session guard and throws a 401 if unauthenticated.

Register it in your route config:

```php
<?php
// app/config/routes.php

Router::middleware(\SDF\Auth\AuthMiddleware::class);

// All routes below require authentication
$config['/dashboard'] = 'Dashboard/index';

// Routes registered before middleware are public
$config['/login']     = ['AuthController/loginForm', 'GET'];
$config['/login']     = ['AuthController/login',     'POST'];
$config['/logout']    = ['AuthController/logout',    'GET'];
$config['/register']  = ['AuthController/register',  'POST'];
```

## 6. Login View

`app/views/auth/login.php`:

```html
<!doctype html>
<html lang="en">
<head><title>Login</title></head>
<body>
  @If(isset($error))
    <p style="color:red">{{ $error }}</p>
  @endIf

  <form method="POST" action="/login">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit">Log In</button>
  </form>
</body>
</html>
```

## 7. Routes

```php
<?php
// app/config/routes.php

// Public routes
$config['/login']     = ['AuthController/loginForm', 'GET'];
$config['/login']     = ['AuthController/login',     'POST'];
$config['/logout']    = ['AuthController/logout',    'GET'];
$config['/register']  = ['AuthController/register',  'POST'];

// Protected routes
Router::middleware(\SDF\Auth\AuthMiddleware::class);
$config['/dashboard'] = 'Dashboard/index';
```

## What You Learned

- Using `Auth::attempt()` for credential-based login
- Using `Auth::user()` to get the authenticated model
- Protecting routes with `AuthMiddleware`
- Registering middleware before protected routes
