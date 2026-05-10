<?php

namespace SDF {
    use Closure;

    class TestAppendMiddlewareA implements \SDF\Middleware
    {
        public function handle(\SDF\Request $request, Closure $next): mixed
        {
            $request->value .= 'A';
            return $next($request);
        }
    }

    class TestAppendMiddlewareB implements \SDF\Middleware
    {
        public function handle(\SDF\Request $request, Closure $next): mixed
        {
            $request->value .= 'B';
            return $next($request);
        }
    }

    class TestShortCircuitMiddleware implements \SDF\Middleware
    {
        public function handle(\SDF\Request $request, Closure $next): mixed
        {
            // Do not call next(), short-circuit and return immediately
            $request->value .= 'STOP';
            return $request;
        }
    }
}
