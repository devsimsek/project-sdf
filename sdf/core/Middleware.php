<?php

namespace SDF;

use Closure;

/**
 * Interface Middleware
 * Defines the contract for SDF request middlewares.
 */
interface Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param Request  $request The incoming HTTP request.
     * @param Closure $next    The next middleware in the pipeline.
     * @return mixed Response object or data.
     */
    public function handle(Request $request, Closure $next): mixed;
}
