<?php

namespace SDF;

/**
 * Abstract Class Guard
 * Base class for request authorization and authentication checks.
 */
abstract class Guard
{
    /**
     * Determine if the request is authorized.
     *
     * @param Request $request
     * @return bool True if authorized, false otherwise.
     */
    abstract public function authorize(Request $request): bool;
}
