<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Benchmark;
use SDF\Controller;
use SDF\Core;
use SDF\Guard;
use SDF\Library;
use SDF\Loader;
use SDF\Model;
use SDF\Request;
use SDF\Router;
use SDF\Middleware\LiveReloadMiddleware;
use SDF\TestAppendMiddlewareA;
use SDF\TestRequest;

class CoreSurfaceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/sdf_surface_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->resetCoreState();
        $this->resetRouterState();
        putenv('SDF_LIVE_RELOAD');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
        $this->resetCoreState();
        $this->resetRouterState();
        putenv('SDF_LIVE_RELOAD');
        parent::tearDown();
    }

    public function test_core_scan_directory_returns_matching_files(): void
    {
        file_put_contents($this->tmpDir . '/a.php', '<?php');
        file_put_contents($this->tmpDir . '/b.json', '{}');
        file_put_contents($this->tmpDir . '/c.txt', 'x');

        $files = Core::coreScanDirectory($this->tmpDir, '.{php,json}');
        sort($files);

        $this->assertSame(['a.php', 'b.json'], $files);
    }

    public function test_coreGetConfig_uses_injected_state(): void
    {
        $this->setStaticProperty(Core::class, 'config', [
            'app' => ['site_name' => 'demo'],
            'database' => ['driver' => 'sqlite'],
        ]);

        $this->assertSame('demo', Core::coreGetConfig('app', 'site_name'));
        $this->assertSame(['site_name' => 'demo'], Core::coreGetConfig('app'));
        $this->assertFalse(Core::coreGetConfig('app', 'missing'));
    }

    public function test_benchmark_marks_elapsedTime_and_memory_token(): void
    {
        $benchmark = new Benchmark();
        $benchmark->mark('start');
        usleep(1000);
        $benchmark->mark('end');

        $elapsed = $benchmark->elapsedTime('start', 'end', 6);

        $this->assertMatchesRegularExpression('/^\d+\.\d{6}$/', $elapsed);
        $this->assertSame('{elapsed_time}', $benchmark->elapsedTime());
        $this->assertSame('{memory_usage}', $benchmark->memoryUsage());
    }

    public function test_controller_initializes_dependencies_and_reads_config(): void
    {
        $this->setStaticProperty(Core::class, 'config', [
            'app' => ['site_name' => 'demo'],
            'database' => ['driver' => 'sqlite'],
        ]);

        $controller = new Controller();
        $fuse = $this->getObjectProperty($controller, 'fuse');

        $this->assertInstanceOf(Loader::class, $controller->load);
        $this->assertInstanceOf(Request::class, $controller->request);
        $this->assertInstanceOf(\SDF\Response::class, $controller->response);
        $this->assertInstanceOf(\SDF\Fuse::class, $fuse);
        $this->assertSame('demo', $controller->getConfig('site_name'));
        $this->assertSame(['driver' => 'sqlite'], $controller->loadConfig('database'));
    }

    public function test_model_library_and_guard_classes_behave_as_expected(): void
    {
        $library = new Library();
        $model = new Model();
        $guard = new class extends Guard {
            public function authorize(Request $request): bool
            {
                return !empty($request->token);
            }
        };
        $request = new class extends Request {
            public string $token = 'ok';
        };

        $this->assertInstanceOf(Library::class, $library);
        $this->assertInstanceOf(Loader::class, $model->load);
        $this->assertTrue($guard->authorize($request));
    }

    public function test_router_registers_routes_middlewares_and_callbacks(): void
    {
        Router::add('/users/{id}', 'Users/show', 'get');
        Router::middleware(TestAppendMiddlewareA::class);
        Router::pathNotFound('eh_pathNotFound');
        Router::methodNotAllowed('eh_methodNotAllowed');
        $this->assertTrue(Router::setRConfig('debug', true));

        $routes = $this->getStaticProperty(Router::class, 'routes');
        $middlewares = $this->getStaticProperty(Router::class, 'middlewares');
        $config = $this->getStaticProperty(Router::class, 'config');

        $this->assertArrayHasKey('/users/([0-9]+)', $routes);
        $this->assertSame('Users/show', $routes['/users/([0-9]+)']['controller']);
        $this->assertContains(TestAppendMiddlewareA::class, $middlewares);
        $this->assertSame('eh_pathNotFound', $config['pathNotFound']);
        $this->assertSame('eh_methodNotAllowed', $config['methodNotAllowed']);
        $this->assertTrue($config['debug']);
    }

    public function test_live_reload_middleware_injects_reload_script(): void
    {
        putenv('SDF_LIVE_RELOAD=true');
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $middleware = new LiveReloadMiddleware();
        $request = new TestRequest();

        $response = $middleware->handle($request, static function (): string {
            return '<html><body>ok</body></html>';
        });

        $this->assertStringContainsString('__sdf_reload_check', $response);
        $this->assertStringContainsString('window.location.reload()', $response);
    }

    private function resetCoreState(): void
    {
        $this->setStaticProperty(Core::class, 'config', []);
    }

    private function resetRouterState(): void
    {
        $this->setStaticProperty(Router::class, 'routes', []);
        $this->setStaticProperty(Router::class, 'middlewares', []);
        $this->setStaticProperty(Router::class, 'config', [
            'debug' => false,
            'magic_routing' => true,
            'controllersDir' => SDF_APP_CONT,
            'pathNotFound' => null,
            'methodNotAllowed' => null,
            'case_matters' => false,
            'trailing_slash_matters' => false,
            'multimatch' => false,
            'basepath' => '/',
        ]);
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($property);
        $prop->setValue(null, $value);
    }

    private function getStaticProperty(string $class, string $property): mixed
    {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($property);
        return $prop->getValue();
    }

    private function getObjectProperty(object $object, string $property): mixed
    {
        $ref = new ReflectionClass($object);
        $prop = $ref->getProperty($property);
        return $prop->getValue($object);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
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
