<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function test_where_populates_wheres_and_bindings(): void
    {
        $qb = new QueryBuilder('items');
        $qb->where('a', '=', 1)->where('b', 2);

        $ref = new \ReflectionClass($qb);
        $wheresP = $ref->getProperty('wheres');
        $bindingsP = $ref->getProperty('bindings');

        $wheres = $wheresP->getValue($qb);
        $bindings = $bindingsP->getValue($qb);

        $this->assertSame(['`a` = ?', '`b` = ?'], $wheres);
        $this->assertSame([1, 2], $bindings);
    }

    public function test_order_by_and_limit_are_set(): void
    {
        $qb = (new QueryBuilder('posts'))->orderBy('created_at', 'DESC')->limit(10);

        $ref = new \ReflectionClass($qb);
        $orderP = $ref->getProperty('orderBy');
        $limitP = $ref->getProperty('limit');

        $this->assertSame(' ORDER BY `created_at` DESC', $orderP->getValue($qb));
        $this->assertSame(' LIMIT 10', $limitP->getValue($qb));
    }

    public function test_get_sql_composition_matches_expected(): void
    {
        $qb = (new QueryBuilder('users'))
            ->where('status', '=', 'active')
            ->where('user_id', '=', 5)
            ->orderBy('id')
            ->limit(2);

        $ref = new \ReflectionClass($qb);
        $tableP = $ref->getProperty('table');
        $wheresP = $ref->getProperty('wheres');
        $orderP = $ref->getProperty('orderBy');
        $limitP = $ref->getProperty('limit');

        $table = $tableP->getValue($qb);
        $wheres = $wheresP->getValue($qb);
        $order = $orderP->getValue($qb) ?? '';
        $limit = $limitP->getValue($qb) ?? '';

        $sql = "SELECT * FROM {$table}";
        if (!empty($wheres)) {
            $sql .= " WHERE " . implode(" AND ", $wheres);
        }
        $sql .= $order;
        $sql .= $limit;

        $this->assertSame('SELECT * FROM `users` WHERE `status` = ? AND `user_id` = ? ORDER BY `id` ASC LIMIT 2', $sql);
    }
}
