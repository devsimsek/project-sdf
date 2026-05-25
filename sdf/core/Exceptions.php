<?php

namespace SDF;

use RuntimeException;

/**
 * Exception types for HTTP response handling.
 */
class HttpResponseException extends RuntimeException
{
    protected int $httpCode;

    public function __construct(string $message = "HTTP response error", int $httpCode = 500, ?\Throwable $previous = null)
    {
        $this->httpCode = $httpCode;
        parent::__construct($message, $httpCode, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}

class HeadersSendException extends HttpResponseException
{
    public function __construct(string $message = "Headers already sent", int $httpCode = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpCode, $previous);
    }
}
