# Tutorial: User Authentication

Implement session-based login, a Guard to protect routes, and an auth middleware.

---

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
                password_hash VARCHAR(255) NOT NULL,
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

---

## 2. User Model

`app/models/User.php`:

```php
<?php

use SDF\Spark\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $rows = self::query()->where('email', '=', $email)->get();
        return $rows[0] ?? null;
    }
}
```

---

## 3. Auth Controller

`app/controllers/AuthController.php`:

```php
<?php

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
        session_start();

        $email    = $this->request->post('email');
        $password = $this->request->post('password');

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->fuse
                ->with('error', 'Invalid email or password.')
                ->render('auth/login');
            return;
        }

        // Store minimal user data in session
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
        ];

        header('Location: /dashboard');
        exit;
    }

    /** GET /logout */
    public function logout(): void
    {
        session_start();
        session_destroy();
        header('Location: /login');
        exit;
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
            'name'          => $body['name'],
            'email'         => $body['email'],
            'password_hash' => password_hash($body['password'], PASSWORD_BCRYPT),
        ]);

        header('Location: /login');
        exit;
    }
}
```

---

## 4. Auth Guard

`app/guards/AuthGuard.php`:

```php
<?php

use SDF\Guard;
use SDF\Request;

class AuthGuard extends Guard
{
    public function authorize(Request $request): bool
    {
        session_start();
        return isset($_SESSION['user']);
    }
}
```

---

## 5. Protected Controller

Any controller that needs auth applies the guard:

```php
<?php

use SDF\Controller;

class Dashboard extends Controller
{
    public function index(): void
    {
        $guard = new AuthGuard();
        if (!$guard->authorize($this->request)) {
            header('Location: /login');
            exit;
        }

        session_start();
        $user = $_SESSION['user'];

        $this->fuse->with(compact('user'))->render('dashboard/index');
    }
}
```

---

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

---

## 7. Routes

```php
<?php
// app/config/routes.php

$config['/login']     = ['AuthController/loginForm', 'GET'];
$config['/login']     = ['AuthController/login',     'POST'];
$config['/logout']    = ['AuthController/logout',    'GET'];
$config['/register']  = ['AuthController/register',  'POST'];
$config['/dashboard'] = 'Dashboard/index';
```

---

## What You Learned

- Storing hashed passwords with `password_hash` / `password_verify`
- Session management in SDF controllers
- Using a `Guard` to protect any controller method
- Rendering login errors via Fuse template variables
