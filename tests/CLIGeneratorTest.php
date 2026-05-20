<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class CLIGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/sdf_cli_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // cleanup
        $dir = new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ($file->isFile()) unlink($file->getRealPath());
            if ($file->isDir()) rmdir($file->getRealPath());
        }
        if (is_dir($this->tmpDir)) rmdir($this->tmpDir);
    }

    public function test_generate_unit_test_file(): void
    {
        $script = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(getcwd() . '/sdf/cli');
        $cmd = "cd " . escapeshellarg($this->tmpDir) . " && $script g test TempUnit --type=unit";
        exec($cmd, $out, $exit);
        $this->assertSame(0, $exit, "CLI exited non-zero: " . implode("\n", $out));
        $this->assertFileExists($this->tmpDir . '/tests/TempUnitTest.php');
    }

    public function test_generate_controller_test_file(): void
    {
        $script = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(getcwd() . '/sdf/cli');
        $cmd = "cd " . escapeshellarg($this->tmpDir) . " && $script g test TempController controller --force";
        exec($cmd, $out, $exit);
        $this->assertSame(0, $exit, "CLI exited non-zero: " . implode("\n", $out));
        $this->assertFileExists($this->tmpDir . '/tests/TempControllerTest.php');
    }
}
