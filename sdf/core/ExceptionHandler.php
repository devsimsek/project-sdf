<?php

namespace SDF;

/**
 * Top-level exception handler to map exceptions to safe HTTP responses.
 */
class ExceptionHandler
{
    /**
     * Handle any uncaught throwable.
     */
    public static function handle(\Throwable $t): void
    {
        // Log the throwable with context
        Logger::log(Level::FATAL, 'Uncaught exception: ' . $t->getMessage(), [
            'exception' => $t,
            'file' => $t->getFile(),
            'line' => $t->getLine(),
        ]);

        // Try to build a safe response
        try {
            $response = new Response();
            $message = 'Internal Server Error';
            $httpCode = 500;

            // If throwable is HttpResponseException use its code/message
            if ($t instanceof HttpResponseException) {
                $httpCode = $t->getHttpCode();
                $message = $t->getMessage();
            }

            // If headers already sent, we cannot set headers; write body only
            if (headers_sent()) {
                // Best-effort: echo a minimal body
                echo "HTTP/" . PHP_SAPI . " " . $httpCode . "\n";
                echo $message;
                return;
            }

            // Use Response to send a minimal safe error body
            $response->text($message, $httpCode);
        } catch (\Throwable $inner) {
            // Last-resort fallback
            error_log('ExceptionHandler failure: ' . $inner->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain');
            }
            echo 'Internal Server Error';
        }
    }
}
