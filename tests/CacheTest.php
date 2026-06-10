<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Cache\Cache;
use SDF\Cache\FileDriver;
use SDF\Cache\MemcachedDriver;
use SDF\Cache\RedisDriver;
use SDF\Core;

class CacheTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/sdf_cache_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        Cache::reset();
        $this->setStaticProperty(Core::class, 'config', [
            'cache' => [
                'driver' => 'file',
                'file' => ['path' => $this->tmpDir, 'prefix' => 'test_'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::reset();
        $this->setStaticProperty(Core::class, 'config', []);
        $this->removeDirectory($this->tmpDir);
    }

    // ─── FileDriver: PSR-16 compliance ────────────────────────────────────────

    public function test_file_driver_get_and_set(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('name', 'Alice');
        $this->assertSame('Alice', $driver->get('name'));
    }

    public function test_file_driver_get_returns_default_on_miss(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $this->assertSame('fallback', $driver->get('missing', 'fallback'));
    }

    public function test_file_driver_delete(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('tmp', 'value');
        $driver->delete('tmp');
        $this->assertNull($driver->get('tmp'));
    }

    public function test_file_driver_has(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('exists', 'yes');
        $this->assertTrue($driver->has('exists'));
        $this->assertFalse($driver->has('nope'));
    }

    public function test_file_driver_clear(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('a', 1);
        $driver->set('b', 2);
        $driver->clear();
        $this->assertNull($driver->get('a'));
        $this->assertNull($driver->get('b'));
    }

    public function test_file_driver_ttl_expiration(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('short', 'gone', 1);
        $this->assertSame('gone', $driver->get('short'));
        sleep(2);
        $this->assertNull($driver->get('short'));
    }

    public function test_file_driver_get_multiple(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('x', 10);
        $driver->set('y', 20);
        $result = $driver->getMultiple(['x', 'y', 'z'], 0);
        $this->assertSame(['x' => 10, 'y' => 20, 'z' => 0], $result);
    }

    public function test_file_driver_set_multiple(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->setMultiple(['a' => 1, 'b' => 2]);
        $this->assertSame(1, $driver->get('a'));
        $this->assertSame(2, $driver->get('b'));
    }

    public function test_file_driver_delete_multiple(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->set('k1', 1);
        $driver->set('k2', 2);
        $driver->set('k3', 3);
        $driver->deleteMultiple(['k1', 'k3']);
        $this->assertNull($driver->get('k1'));
        $this->assertSame(2, $driver->get('k2'));
        $this->assertNull($driver->get('k3'));
    }

    public function test_file_driver_handles_serialized_types(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $data = ['nested' => ['a' => 1, 'b' => true], 'num' => 42, 'flag' => false];
        $driver->set('complex', $data);
        $this->assertSame($data, $driver->get('complex'));
    }

    // ─── FileDriver: Tagging ──────────────────────────────────────────────────

    public function test_file_driver_tagging(): void
    {
        $driver = new FileDriver(['path' => $this->tmpDir, 'prefix' => 'test_']);
        $driver->tags(['people'])->set('user_1', 'Alice');
        $driver->tags(['people'])->set('user_2', 'Bob');
        $driver->tags(['posts'])->set('post_1', 'Hello');

        $this->assertSame('Alice', $driver->get('user_1'));
        $this->assertSame('Bob', $driver->get('user_2'));

        $driver->forgetTags(['people']);

        $this->assertNull($driver->get('user_1'));
        $this->assertNull($driver->get('user_2'));
        $this->assertSame('Hello', $driver->get('post_1'));
    }

    // ─── Cache Facade ─────────────────────────────────────────────────────────

    public function test_cache_facade_get_set_and_forget(): void
    {
        Cache::set('facade_key', 'facade_value');
        $this->assertSame('facade_value', Cache::get('facade_key'));

        Cache::forget('facade_key');
        $this->assertNull(Cache::get('facade_key'));
    }

    public function test_cache_facade_has(): void
    {
        Cache::set('present', 'yes');
        $this->assertTrue(Cache::has('present'));
        $this->assertFalse(Cache::has('absent'));
    }

    public function test_cache_facade_flush(): void
    {
        Cache::set('a', 1);
        Cache::set('b', 2);
        Cache::flush();
        $this->assertNull(Cache::get('a'));
        $this->assertNull(Cache::get('b'));
    }

    public function test_cache_facade_multiple(): void
    {
        Cache::setMultiple(['alpha' => 'a', 'beta' => 'b']);
        $result = Cache::getMultiple(['alpha', 'beta', 'gamma'], 'miss');
        $this->assertSame(['alpha' => 'a', 'beta' => 'b', 'gamma' => 'miss'], $result);

        Cache::deleteMultiple(['alpha']);
        $this->assertNull(Cache::get('alpha'));
        $this->assertSame('b', Cache::get('beta'));
    }

    public function test_cache_facade_ttl(): void
    {
        Cache::set('short_ttl', 'value', 1);
        $this->assertSame('value', Cache::get('short_ttl'));
        sleep(2);
        $this->assertNull(Cache::get('short_ttl'));
    }

    public function test_cache_facade_tags(): void
    {
        Cache::tags(['tag_a'])->set('item_1', 'first');
        Cache::tags(['tag_b'])->set('item_2', 'second');

        $this->assertSame('first', Cache::get('item_1'));
        $this->assertSame('second', Cache::get('item_2'));

        Cache::tags(['tag_a'])->forget('item_1');
        $this->assertNull(Cache::get('item_1'));
    }

    // ─── Graceful fallback ────────────────────────────────────────────────────

    public function test_redis_driver_graceful_fallback_when_not_available(): void
    {
        $driver = new RedisDriver(['host' => '192.0.2.1']);
        $this->assertFalse($driver->isAvailable());
        $this->assertSame('default', $driver->get('any', 'default'));
        $this->assertFalse($driver->set('any', 'value'));
        $this->assertFalse($driver->has('any'));
        $this->assertFalse($driver->delete('any'));
        $this->assertFalse($driver->clear());
    }

    public function test_memcached_driver_graceful_fallback_when_not_available(): void
    {
        $driver = new MemcachedDriver(['host' => '192.0.2.1']);
        $this->assertFalse($driver->isAvailable());
        $this->assertSame('default', $driver->get('any', 'default'));
        $this->assertFalse($driver->set('any', 'value'));
        $this->assertFalse($driver->has('any'));
        $this->assertFalse($driver->delete('any'));
        $this->assertFalse($driver->clear());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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
