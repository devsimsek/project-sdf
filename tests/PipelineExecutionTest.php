<?php

namespace Tests;

require_once __DIR__ . '/TestMiddlewares.php';
require_once __DIR__ . '/TestRequest.php';

use PHPUnit\Framework\TestCase;
use SDF\Pipeline;
use SDF\TestRequest as Request;

class PipelineExecutionTest extends TestCase
{
    public function test_middlewares_execute_in_order_and_call_next(): void
    {
        $req = new Request();
        $req->value = '';

        $pipeline = (new Pipeline())
            ->send($req)
            ->through([
                \SDF\TestAppendMiddlewareA::class,
                \SDF\TestAppendMiddlewareB::class,
            ]);

        $result = $pipeline->then(function (Request $r) {
            $r->value .= 'Z';
            return $r;
        });

        $this->assertSame('ABZ', $result->value);
    }

    public function test_middleware_short_circuits_pipeline(): void
    {
        $req = new Request();
        $req->value = '';

        $pipeline = (new Pipeline())
            ->send($req)
            ->through([
                \SDF\TestAppendMiddlewareA::class,
                \SDF\TestShortCircuitMiddleware::class,
                \SDF\TestAppendMiddlewareB::class,
            ]);

        $result = $pipeline->then(function (Request $r) {
            $r->value .= 'Z';
            return $r;
        });

        // Short-circuit middleware should prevent later middleware and the final destination from executing
        $this->assertSame('ASTOP', $result->value);
    }
}
