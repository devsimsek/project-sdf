<?php

/**
 * Path Not Found Error Handler.
 * Called By Router.
 * @param $requestPath
 * @return void
 */
function eh_pathNotFound($requestPath): void
{
    if (!is_array($requestPath)) {
        print_r('404 Error, Path ' . $requestPath . ' Not Found.');
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
    print_r("Method " . $requestMethod . " not allowed on this path.");
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
    if (ob_get_level() > 0) ob_end_clean();

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