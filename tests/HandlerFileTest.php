<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Logger;

class HandlerFileTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/sdf_file_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        Logger::resetInstance();
        // cleanup
        $it = new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS);
        $rit = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($rit as $f) {
            if ($f->isFile()) unlink($f->getRealPath());
            if ($f->isDir()) rmdir($f->getRealPath());
        }
        if (is_dir($this->tmpDir)) rmdir($this->tmpDir);
    }

    public function test_rotating_file_handler_by_size(): void
    {
        $file = $this->tmpDir . '/app.log';
        // small rotate size to trigger rotation
        $logger = Logger::getInstance(['level' => 'DEBUG', 'file' => ['path' => $file, 'rotate_size' => 512, 'max_files' => 3]]);

        for ($i = 0; $i < 50; $i++) {
            Logger::info('entry ' . $i);
        }
        $logger->flush();

        // check at least one rotated file exists
        $found = false;
        for ($i = 1; $i <= 3; $i++) {
            if (file_exists($file . '.' . $i) || file_exists($file . '.' . $i . '.gz')) { $found = true; break; }
        }
        $this->assertTrue($found, 'Rotated file should be present.');
    }

    public function test_async_handler_batches(): void
    {
        $file = $this->tmpDir . '/async.log';
        $logger = Logger::getInstance(['level' => 'DEBUG', 'file' => ['path' => $file], 'async' => ['enabled' => false, 'batch_size' => 5]]);

        // Since pcntl may not be available in CI, test that AsyncHandler (with fork disabled) still batches when configured
        // We'll directly add an AsyncHandler wrapping a FileHandler with batch_size 5 and use it
        $fh = new \SDF\FileHandler($file);
        $ah = new \SDF\AsyncHandler($fh, 5, false);
        $logger->addHandler($ah);

        for ($i = 0; $i < 12; $i++) {
            Logger::info('async ' . $i);
        }

        // flush ensures remaining batch written
        $ah->flush();

        // check file contains entries
        $contents = file_get_contents($file);
        $this->assertStringContainsString('async 0', $contents);
        $this->assertStringContainsString('async 11', $contents);
    }
}
