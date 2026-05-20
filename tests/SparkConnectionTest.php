<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Spark;

class SparkConnectionTest extends TestCase
{
    public function test_pdo_throws_when_not_connected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Spark ORM: Database not connected.');
        Spark::pdo();
    }
}
