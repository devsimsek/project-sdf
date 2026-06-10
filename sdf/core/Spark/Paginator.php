<?php

/**
 * smskSoft SDF Spark Paginator
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Spark
 * @file        Paginator.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/spark.md#paginator
 * @since       Version 2.1
 * @filesource
 */

declare(strict_types=1);

namespace SDF\Spark;

class Paginator
{
    private array $items;
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $lastPage;

    public function __construct(array $items, int $total, int $perPage, int $currentPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = (int)ceil($total / max($perPage, 1));
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function hasMore(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function onFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(fn ($item) => is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item, $this->items),
            'pagination' => [
                'total' => $this->total,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'has_more' => $this->hasMore(),
            ],
        ];
    }
}
