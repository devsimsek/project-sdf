<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Fuse;
use SDF\Loader;

class LoaderFuseTest extends TestCase
{
    private string $tmpDir;
    private string $appFixtureDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/sdf_loader_' . uniqid();
        $this->appFixtureDir = SDF_APP . 'loader-fixtures/';
        mkdir($this->tmpDir, 0755, true);
        mkdir($this->appFixtureDir, 0755, true);
        $this->resetLoaderState();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
        $this->removeDirectory($this->appFixtureDir);
        $this->resetLoaderState();
        parent::tearDown();
    }

    public function test_view_renders_with_extracted_params(): void
    {
        $viewDir = $this->tmpDir . '/views/';
        mkdir($viewDir, 0755, true);
        file_put_contents($viewDir . 'hello.php', 'Hello <?= $name ?>');

        $loader = new Loader();

        ob_start();
        $result = $loader->view('hello', ['name' => 'World'], $viewDir);
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertSame('Hello World', trim($output));
    }

    public function test_helper_and_file_loaders_return_required_values(): void
    {
        $helperDir = $this->tmpDir . '/helpers/';
        $fileDir = $this->tmpDir . '/files/';
        mkdir($helperDir, 0755, true);
        mkdir($fileDir, 0755, true);
        file_put_contents($helperDir . 'answer.php', "<?php\nreturn 42;\n");
        file_put_contents($fileDir . 'payload.php', "<?php\nreturn 'done';\n");

        $loader = new Loader();

        $this->assertTrue($loader->helper('answer', $helperDir));
        $this->assertSame('done', $loader->file('payload', $fileDir));
    }

    public function test_model_and_library_loaders_instantiate_classes(): void
    {
        $modelDir = $this->tmpDir . '/models/';
        $libraryDir = $this->tmpDir . '/libraries/';
        mkdir($modelDir, 0755, true);
        mkdir($libraryDir, 0755, true);

        file_put_contents(
            $modelDir . 'Samplemodel.php',
            "<?php\nclass Samplemodel {}\n"
        );
        file_put_contents(
            $libraryDir . 'Samplelibrary.php',
            "<?php\nclass Samplelibrary {\n    public array \$args;\n    public function __construct(...\$args) { \$this->args = \$args; }\n}\n"
        );

        $loader = new Loader();
        $model = $loader->model('Samplemodel', $modelDir);
        $library = $loader->library('Samplelibrary', ['alpha'], $libraryDir);

        $this->assertInstanceOf('Samplemodel', $model);
        $this->assertInstanceOf('Samplelibrary', $library);
        $this->assertSame(['alpha'], $library->args);
    }

    public function test_config_loader_reads_php_config(): void
    {
        file_put_contents(
            $this->appFixtureDir . 'demo.php',
            "<?php\n\$config = ['answer' => 42];\n"
        );

        $loader = new Loader();
        $config = $loader->config('demo.php', 'loader-fixtures');

        $this->assertSame(['answer' => 42], $config);
    }

    public function test_fuse_renders_and_escapes_variables(): void
    {
        $viewDir = $this->tmpDir . '/fuse-views/';
        mkdir($viewDir, 0755, true);
        file_put_contents($viewDir . 'hello.php', 'Hello {{ $name }}');

        $fuse = new Fuse();
        $output = $fuse->with(['name' => '<b>World</b>'])->render('hello', $viewDir);

        $this->assertSame('Hello &lt;b&gt;World&lt;/b&gt;', trim($output));
    }

    private function resetLoaderState(): void
    {
        $ref = new \ReflectionClass(Loader::class);
        $prop = $ref->getProperty('isLoaded');
        if (PHP_VERSION_ID < 80100) {
            $prop->setAccessible(true);
        }
        $prop->setValue(null, []);
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
