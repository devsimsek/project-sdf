<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Auth\Auth;
use SDF\Auth\AuthMiddleware;
use SDF\Auth\Guard;
use SDF\Auth\JwtGuard;
use SDF\Auth\SessionGuard;
use SDF\Auth\UserProvider;
use SDF\Core;
use SDF\HttpResponseException;
use SDF\Request;

/**
 * Stub user model for auth testing.
 *
 * Mimics the Spark\Model static API (find(), where()->first())
 * with a simple in-memory store.
 */
class AuthTestUser
{
    public int $id;
    public string $email;
    public string $password;
    public string $name;

    /** @var array<int, AuthTestUser> In-memory store. */
    private static array $store = [];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function resetStore(): void
    {
        self::$store = [];
    }

    public static function seed(array $data): self
    {
        $user = new self($data);
        self::$store[$user->id] = $user;
        return $user;
    }

    public static function find(mixed $id): ?self
    {
        return self::$store[$id] ?? null;
    }

    public static function where(string $column, mixed $value): object
    {
        return new class($column, $value, self::$store) {
            private string $column;
            private mixed $value;
            private array $store;

            public function __construct(string $c, mixed $v, array $s)
            {
                $this->column = $c;
                $this->value = $v;
                $this->store = $s;
            }

            public function first(): ?object
            {
                foreach ($this->store as $user) {
                    if (($user->{$this->column} ?? null) === $this->value) {
                        return $user;
                    }
                }
                return null;
            }
        };
    }
}

class AuthTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        Auth::reset();
        AuthTestUser::resetStore();

        $this->tmpDir = sys_get_temp_dir() . '/sdf_auth_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->setStaticProperty(Core::class, 'config', [
            'auth' => [
                'default' => 'session',
                'guards' => [
                    'session' => ['provider' => 'users'],
                    'jwt' => [
                        'provider' => 'users',
                        'secret' => 'test-secret-key-012345678901234567890123456789',
                        'ttl' => 3600,
                        'refresh_ttl' => 604800,
                    ],
                ],
                'providers' => [
                    'users' => ['model' => AuthTestUser::class],
                ],
            ],
        ]);

        AuthTestUser::seed([
            'id' => 1,
            'email' => 'alice@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'name' => 'Alice',
        ]);
    }

    protected function tearDown(): void
    {
        Auth::reset();
        AuthTestUser::resetStore();
        $this->setStaticProperty(Core::class, 'config', []);
        $this->removeDirectory($this->tmpDir);
        $_SESSION = [];
    }

    // ─── SessionGuard ─────────────────────────────────────────────────────────

    public function test_session_guard_check_returns_false_when_not_authenticated(): void
    {
        $this->assertFalse(Auth::guard('session')->check());
    }

    public function test_session_guard_user_returns_null_when_not_authenticated(): void
    {
        $this->assertNull(Auth::guard('session')->user());
    }

    public function test_session_guard_login_and_check(): void
    {
        $user = AuthTestUser::find(1);
        Auth::guard('session')->login($user);
        $this->assertTrue(Auth::guard('session')->check());
    }

    public function test_session_guard_user_returns_hydrated_model(): void
    {
        $user = AuthTestUser::find(1);
        Auth::guard('session')->login($user);
        $retrieved = Auth::guard('session')->user();
        $this->assertInstanceOf(AuthTestUser::class, $retrieved);
        $this->assertSame('alice@example.com', $retrieved->email);
    }

    public function test_session_guard_logout_clears_state(): void
    {
        $user = AuthTestUser::find(1);
        Auth::guard('session')->login($user);
        Auth::guard('session')->logout();
        $this->assertFalse(Auth::guard('session')->check());
        $this->assertNull(Auth::guard('session')->user());
    }

    public function test_session_guard_attempt_with_valid_credentials(): void
    {
        $result = Auth::guard('session')->attempt([
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]);
        $this->assertTrue($result);
        $this->assertTrue(Auth::guard('session')->check());
    }

    public function test_session_guard_attempt_with_invalid_password(): void
    {
        $result = Auth::guard('session')->attempt([
            'email' => 'alice@example.com',
            'password' => 'wrong',
        ]);
        $this->assertFalse($result);
        $this->assertFalse(Auth::guard('session')->check());
    }

    public function test_session_guard_attempt_with_unknown_user(): void
    {
        $result = Auth::guard('session')->attempt([
            'email' => 'bob@example.com',
            'password' => 'secret123',
        ]);
        $this->assertFalse($result);
    }

    // ─── JwtGuard ─────────────────────────────────────────────────────────────

    public function test_jwt_guard_check_returns_false_without_token(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $this->assertFalse(Auth::guard('jwt')->check());
    }

    public function test_jwt_guard_issue_token_and_authenticate(): void
    {
        $user = AuthTestUser::find(1);
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $token = $guard->issueToken($user);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        $this->assertTrue($guard->check());
        $this->assertSame(1, $guard->user()->id);
    }

    public function test_jwt_guard_rejects_expired_token(): void
    {
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $token = $guard->encode([
            'sub' => 1,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'type' => 'access',
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        $this->assertFalse($guard->check());
    }

    public function test_jwt_guard_rejects_tampered_token(): void
    {
        $user = AuthTestUser::find(1);
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $token = $guard->issueToken($user);
        $tampered = substr($token, 0, -5) . 'XXXXX';

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $tampered";
        $this->assertFalse($guard->check());
    }

    public function test_jwt_guard_login_and_user(): void
    {
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $user = AuthTestUser::find(1);
        $guard->login($user);
        $this->assertTrue($guard->check());
        $this->assertSame(1, $guard->user()->id);
    }

    public function test_jwt_guard_logout(): void
    {
        $guard = Auth::guard('jwt');
        $guard->login(AuthTestUser::find(1));
        $guard->logout();
        $this->assertFalse($guard->check());
    }

    public function test_jwt_guard_attempt(): void
    {
        $guard = Auth::guard('jwt');
        $result = $guard->attempt([
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]);
        $this->assertTrue($result);
    }

    // ─── JWT Refresh ───────────────────────────────────────────────────────────

    public function test_jwt_guard_issue_and_verify_refresh_token(): void
    {
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $user = AuthTestUser::find(1);
        $refreshToken = $guard->issueRefreshToken($user);
        $this->assertIsString($refreshToken);

        $result = $guard->refresh($refreshToken);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertSame('Bearer', $result['token_type']);

        // New access token should authenticate
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $result['access_token'];
        $this->assertTrue($guard->check());
    }

    public function test_jwt_guard_refresh_rejects_expired_token(): void
    {
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $expiredRefresh = $guard->encode([
            'sub' => 1,
            'iat' => time() - 700000,
            'exp' => time() - 3600,
            'type' => 'refresh',
        ]);

        $this->assertNull($guard->refresh($expiredRefresh));
    }

    public function test_jwt_guard_refresh_rejects_access_token(): void
    {
        $guard = Auth::guard('jwt');

        if (!$guard instanceof JwtGuard) {
            $this->fail('Expected JwtGuard');
        }

        $user = AuthTestUser::find(1);
        $accessToken = $guard->issueToken($user);
        $this->assertNull($guard->refresh($accessToken));
    }

    // ─── Auth Facade ───────────────────────────────────────────────────────────

    public function test_auth_facade_check_delegates_to_default_guard(): void
    {
        $this->assertFalse(Auth::check());
        Auth::login(AuthTestUser::find(1));
        $this->assertTrue(Auth::check());
    }

    public function test_auth_facade_user_returns_authenticated_user(): void
    {
        Auth::login(AuthTestUser::find(1));
        $user = Auth::user();
        $this->assertInstanceOf(AuthTestUser::class, $user);
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_auth_facade_logout(): void
    {
        Auth::login(AuthTestUser::find(1));
        Auth::logout();
        $this->assertFalse(Auth::check());
    }

    public function test_auth_facade_attempt(): void
    {
        $result = Auth::attempt([
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]);
        $this->assertTrue($result);
    }

    public function test_auth_facade_issue_token_returns_null_for_session_guard(): void
    {
        $this->assertNull(Auth::issueToken(AuthTestUser::find(1)));
    }

    public function test_auth_facade_refresh_returns_null_for_session_guard(): void
    {
        $this->assertNull(Auth::refresh('some-token'));
    }

    // ─── AuthMiddleware ────────────────────────────────────────────────────────

    public function test_auth_middleware_passes_when_authenticated(): void
    {
        Auth::login(AuthTestUser::find(1));
        $request = new Request();

        $middleware = new AuthMiddleware('session');
        $response = $middleware->handle($request, function ($req) {
            return 'allowed';
        });
        $this->assertSame('allowed', $response);
    }

    public function test_auth_middleware_throws_401_when_not_authenticated(): void
    {
        $request = new Request();
        $middleware = new AuthMiddleware('session');

        $this->expectException(HttpResponseException::class);
        $this->expectExceptionMessage('Unauthenticated');

        $middleware->handle($request, function ($req) {
            return 'should not reach';
        });
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($property);
        $prop->setValue(null, $value);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        rmdir($directory);
    }
}
