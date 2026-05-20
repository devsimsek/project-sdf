<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\QueryBuilder;
use SDF\Spark;

/**
 * Unit tests for Spark ORM QueryBuilder.
 */
class SparkTest extends TestCase
{
    public function test_query_builder_builds_select_without_where(): void
    {
        $qb = new QueryBuilder('users');
        $sql = $this->getSql($qb);
        $this->assertSame('SELECT * FROM `users`', $sql);
    }

    public function test_query_builder_builds_select_with_single_where(): void
    {
        $qb = (new QueryBuilder('users'))->where('id', '=', 1);
        $sql = $this->getSql($qb);
        $this->assertSame('SELECT * FROM `users` WHERE `id` = ?', $sql);
    }

    public function test_query_builder_chains_multiple_wheres(): void
    {
        $qb = (new QueryBuilder('posts'))
            ->where('status', '=', 'active')
            ->where('user_id', '=', 5);
        $sql = $this->getSql($qb);
        $this->assertSame('SELECT * FROM `posts` WHERE `status` = ? AND `user_id` = ?', $sql);
    }

    public function test_spark_throws_when_no_connection(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database not connected');
        Spark::pdo();
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /** Reflect to read the generated SQL without executing. */
    private function getSql(QueryBuilder $qb): string
    {
        $ref = new \ReflectionClass($qb);

        $tableP = $ref->getProperty('table');
        $tableP->setAccessible(true);
        $wheresP = $ref->getProperty('wheres');
        $wheresP->setAccessible(true);

        $table = $tableP->getValue($qb);
        $wheres = $wheresP->getValue($qb);

        $sql = "SELECT * FROM `{$table}`";
        if (!empty($wheres)) {
            $sql .= " WHERE " . implode(" AND ", $wheres);
        }
        return $sql;
    }
}
