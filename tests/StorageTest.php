<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Core;
use SDF\Storage\Drivers\LocalDriver;
use SDF\Storage\Storage;

class StorageTest extends TestCase
{
    private string $tempRoot;
    private LocalDriver $driver;

    protected function setUp(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/sdf_storage_test_' . uniqid();
        mkdir($this->tempRoot, 0755, true);
        $this->driver = new LocalDriver($this->tempRoot);
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tempRoot);
        Storage::reset();
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_put_and_exists(): void
    {
        $this->driver->put('hello.txt', 'world');
        $this->assertTrue($this->driver->exists('hello.txt'));
    }

    public function test_get_returns_contents(): void
    {
        $this->driver->put('hello.txt', 'world');
        $this->assertSame('world', $this->driver->get('hello.txt'));
    }

    public function test_get_returns_null_when_missing(): void
    {
        $this->assertNull($this->driver->get('nonexistent.txt'));
    }

    public function test_delete_removes_file(): void
    {
        $this->driver->put('delete_me.txt', 'gone');
        $this->assertTrue($this->driver->exists('delete_me.txt'));
        $this->assertTrue($this->driver->delete('delete_me.txt'));
        $this->assertFalse($this->driver->exists('delete_me.txt'));
    }

    public function test_delete_returns_false_when_missing(): void
    {
        $this->assertFalse($this->driver->delete('nonexistent.txt'));
    }

    public function test_size(): void
    {
        $this->driver->put('size_test.txt', '12345');
        $this->assertSame(5, $this->driver->size('size_test.txt'));
    }

    public function test_size_returns_zero_when_missing(): void
    {
        $this->assertSame(0, $this->driver->size('nonexistent.txt'));
    }

    public function test_mime_type(): void
    {
        $this->driver->put('test.txt', 'plain text');
        $mime = $this->driver->mimeType('test.txt');
        $this->assertStringContainsString('text', $mime);
    }

    public function test_mime_type_returns_default_when_missing(): void
    {
        $this->assertSame('application/octet-stream', $this->driver->mimeType('nonexistent'));
    }

    public function test_files_lists_only_files(): void
    {
        $this->driver->put('a.txt', 'a');
        $this->driver->put('b.txt', 'b');
        $this->driver->makeDirectory('sub');
        $this->driver->put('sub/c.txt', 'c');

        $files = $this->driver->files();
        sort($files);
        $this->assertSame(['a.txt', 'b.txt'], $files);
    }

    public function test_files_in_subdirectory(): void
    {
        $this->driver->makeDirectory('sub');
        $this->driver->put('sub/c.txt', 'c');
        $this->driver->put('sub/d.txt', 'd');

        $files = $this->driver->files('sub');
        sort($files);
        $this->assertSame(['sub/c.txt', 'sub/d.txt'], $files);
    }

    public function test_all_files_recursive(): void
    {
        $this->driver->put('a.txt', 'a');
        $this->driver->makeDirectory('sub');
        $this->driver->put('sub/b.txt', 'b');
        $this->driver->makeDirectory('sub/deep');
        $this->driver->put('sub/deep/c.txt', 'c');

        $files = $this->driver->allFiles();
        $this->assertCount(3, $files);
    }

    public function test_directories(): void
    {
        $this->driver->put('a.txt', 'a');
        $this->driver->makeDirectory('sub1');
        $this->driver->makeDirectory('sub2');

        $dirs = $this->driver->directories();
        sort($dirs);
        $this->assertSame(['sub1', 'sub2'], $dirs);
    }

    public function test_copy(): void
    {
        $this->driver->put('source.txt', 'content');
        $this->assertTrue($this->driver->copy('source.txt', 'dest.txt'));
        $this->assertTrue($this->driver->exists('dest.txt'));
        $this->assertSame('content', $this->driver->get('dest.txt'));
    }

    public function test_move(): void
    {
        $this->driver->put('original.txt', 'moved');
        $this->assertTrue($this->driver->move('original.txt', 'moved.txt'));
        $this->assertFalse($this->driver->exists('original.txt'));
        $this->assertTrue($this->driver->exists('moved.txt'));
        $this->assertSame('moved', $this->driver->get('moved.txt'));
    }

    public function test_make_directory(): void
    {
        $this->assertTrue($this->driver->makeDirectory('newdir'));
        $this->assertTrue(is_dir($this->tempRoot . '/newdir'));
    }

    public function test_delete_directory(): void
    {
        $this->driver->makeDirectory('todelete');
        $this->driver->put('todelete/file.txt', 'x');
        $this->assertTrue($this->driver->deleteDirectory('todelete'));
        $this->assertFalse(is_dir($this->tempRoot . '/todelete'));
    }

    public function test_stream(): void
    {
        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, 'stream content');
        rewind($resource);

        $this->assertTrue($this->driver->stream('streamed.txt', $resource));
        $this->assertSame('stream content', $this->driver->get('streamed.txt'));
    }

    public function test_url(): void
    {
        $driver = new LocalDriver('/tmp', '/uploads');
        $this->assertSame('/uploads/file.jpg', $driver->url('file.jpg'));
    }

    public function test_url_no_prefix(): void
    {
        $this->assertSame('file.jpg', $this->driver->url('file.jpg'));
    }

    public function test_facade_put_and_exists(): void
    {
        $this->setUpStorageConfig();
        Storage::put('facade_test.txt', 'facade content');
        $this->assertTrue(Storage::exists('facade_test.txt'));
        $this->assertSame('facade content', Storage::get('facade_test.txt'));
    }

    public function test_facade_delete(): void
    {
        $this->setUpStorageConfig();
        Storage::put('facade_del.txt', 'x');
        Storage::delete('facade_del.txt');
        $this->assertFalse(Storage::exists('facade_del.txt'));
    }

    private function setUpStorageConfig(): void
    {
        $refl = new \ReflectionProperty(Core::class, 'config');
        $refl->setAccessible(true);
        $refl->setValue(null, [
            'storage' => [
                'default' => 'local',
                'disks' => [
                    'local' => [
                        'root' => $this->tempRoot,
                        'url' => '',
                    ],
                ],
            ],
        ]);
        Storage::reset();
    }
}
