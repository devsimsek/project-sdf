<?php
/**
 * SDF Opcache Warmup Script
 * Run after deploy to pre-compile framework files into shared memory.
 *
 * Usage: php sdf/warmup.php
 *
 * Requires: opcache.enable=On and opcache.enable_cli=On (for CLI)
 * Production: run via web request or FPM post-deploy hook
 */

$root = dirname(__DIR__);
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/sdf/core', RecursiveDirectoryIterator::SKIP_DOTS)
);

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getRealPath();
    if (function_exists('opcache_compile_file') && !in_array($path, get_included_files(), true)) {
        @opcache_compile_file($path);
        $count++;
    }
}

echo "Warmed up $count files.\n";
