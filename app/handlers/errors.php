<?php

/**
 * Path Not Found Error Handler.
 * Called By Router.
 * @param $requestPath
 * @return void
 */
function eh_pathNotFound($requestPath): void
{
    if (!headers_sent()) {
        header('HTTP/1.0 404 Not Found');
        header('Content-Type: text/html; charset=UTF-8');
    }
    if (!is_array($requestPath)) {
        $safePath = htmlspecialchars($requestPath, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        print_r('404 Error, Path ' . $safePath . ' Not Found.');
    } else {
        print_r('404 Error, Path Not Found.');
    }
    exit();
}

/**
 * Method Not Allowed Error Handler.
 * Called By Router.
 * @param $requestPath
 * @param $requestMethod
 * @return void
 */
function eh_methodNotAllowed($requestPath, $requestMethod): void
{
    if (!headers_sent()) {
        header('HTTP/1.0 405 Method Not Allowed');
        header('Content-Type: text/html; charset=UTF-8');
    }
    $safeMethod = htmlspecialchars($requestMethod, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    $safePath = is_array($requestPath) ? '' : ' ' . htmlspecialchars($requestPath, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    print_r("Method " . $safeMethod . " not allowed on this path" . $safePath . ".");
    exit();
}

/**
 * Error Handler. (Called by core.)
 *
 * @param int $errnum
 * @param string $errmessage
 * @param string|null $errfile
 * @param int $errline
 * @return void
 */
function eh_errorHandler(int $errnum, string $errmessage, ?string $errfile = null, int $errline = 0): void
{
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    $safeMessage = htmlspecialchars($errmessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeFile = $errfile ? htmlspecialchars($errfile, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;

    echo "<div style='border: 1px solid #ff0000; padding: 20px; background: #fff5f5; font-family: sans-serif; color: #333;'>";
    echo "<h2 style='color: #d00; margin-top: 0;'>SDF Framework Error</h2>";
    echo "<p><strong>Message:</strong> $safeMessage</p>";
    echo "<p><strong>Error Number:</strong> $errnum</p>";
    if ($safeFile) {
        echo "<p><strong>File:</strong> $safeFile</p>";
        echo "<p><strong>Line:</strong> $errline</p>";
    }
    echo "</div>";
    exit(1);
}
