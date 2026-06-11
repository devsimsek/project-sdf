<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Spark\Paginator;

class PaginatorTest extends TestCase
{
    public function test_returns_items(): void
    {
        $p = new Paginator(['a', 'b'], 10, 2, 1);
        $this->assertSame(['a', 'b'], $p->items());
    }

    public function test_total(): void
    {
        $p = new Paginator([], 42, 15, 1);
        $this->assertSame(42, $p->total());
    }

    public function test_per_page(): void
    {
        $p = new Paginator([], 42, 15, 1);
        $this->assertSame(15, $p->perPage());
    }

    public function test_current_page(): void
    {
        $p = new Paginator([], 42, 15, 3);
        $this->assertSame(3, $p->currentPage());
    }

    public function test_last_page_calculation(): void
    {
        $p = new Paginator([], 42, 15, 1);
        $this->assertSame(3, $p->lastPage());

        $p2 = new Paginator([], 45, 15, 1);
        $this->assertSame(3, $p2->lastPage());

        $p3 = new Paginator([], 46, 15, 1);
        $this->assertSame(4, $p3->lastPage());
    }

    public function test_has_more_when_not_on_last_page(): void
    {
        $p = new Paginator([], 42, 15, 1);
        $this->assertTrue($p->hasMore());

        $p2 = new Paginator([], 42, 15, 3);
        $this->assertFalse($p2->hasMore());
    }

    public function test_has_pages_when_multiple_pages(): void
    {
        $p = new Paginator([], 100, 10, 1);
        $this->assertTrue($p->hasPages());
    }

    public function test_no_pages_when_single_page(): void
    {
        $p = new Paginator([], 5, 10, 1);
        $this->assertFalse($p->hasPages());
    }

    public function test_on_first_page(): void
    {
        $p = new Paginator([], 42, 15, 1);
        $this->assertTrue($p->onFirstPage());

        $p2 = new Paginator([], 42, 15, 2);
        $this->assertFalse($p2->onFirstPage());
    }

    public function test_is_empty(): void
    {
        $p = new Paginator([], 0, 15, 1);
        $this->assertTrue($p->isEmpty());
        $this->assertFalse($p->isNotEmpty());
    }

    public function test_is_not_empty(): void
    {
        $p = new Paginator(['x'], 1, 15, 1);
        $this->assertFalse($p->isEmpty());
        $this->assertTrue($p->isNotEmpty());
    }

    public function test_count(): void
    {
        $p = new Paginator(['a', 'b', 'c'], 10, 3, 1);
        $this->assertSame(3, $p->count());
    }

    public function test_to_array(): void
    {
        $p = new Paginator(['a', 'b'], 20, 10, 2);
        $result = $p->toArray();
        $this->assertSame(['a', 'b'], $result['items']);
        $this->assertSame(20, $result['pagination']['total']);
        $this->assertSame(10, $result['pagination']['per_page']);
        $this->assertSame(2, $result['pagination']['current_page']);
        $this->assertSame(2, $result['pagination']['last_page']);
        $this->assertFalse($result['pagination']['has_more']);
    }

    public function test_get_iterator(): void
    {
        $items = ['a', 'b', 'c'];
        $p = new Paginator($items, 3, 10, 1);
        $iter = $p->getIterator();
        $this->assertInstanceOf(\ArrayIterator::class, $iter);
        $this->assertCount(3, $iter);
    }

    public function test_handles_zero_total(): void
    {
        $p = new Paginator([], 0, 15, 1);
        $this->assertSame(0, $p->lastPage());
        $this->assertFalse($p->hasMore());
        $this->assertFalse($p->hasPages());
        $this->assertTrue($p->isEmpty());
    }
}
