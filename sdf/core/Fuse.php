<?php

namespace SDF;

/**
 * Fuse View Engine
 * A custom view Engine for SDF framework.
 *
 * @package     Fuse
 * @file        Fuse.php
 * @version     v2.0.0
 * @changelog   v2.0.0 - Removed eval(). Templates compile to cached PHP files. XSS escaping added.
 * @author      devsimsek
 * @copyright   Copyright (C) 2023 smskSoft and devsimsek
 * @license     https://devsimsek.mit-license.org
 * @url         https://github.com/devsimsek/Fuse
 * @since       v1.0
 * @filesource
 */
class Fuse
{
    /** @var array<string, string> Static cache for resolved view paths */
    private static array $resolvedViewCache = [];

    /** @var array<string, int> Static cache for source file mtimes */
    private static array $sourceMtimeCache = [];

    /**
     * Data storage.
     * @param array $data
     */
    protected array $data = [];

    public function __construct()
    {
        if (!defined("SDF") && !defined("SDF_APP_VIEW")) {
            define("SDF", false);
            define("SDF_APP_VIEW", getcwd() . "/views/");
        }
    }

    /**
     * Assign data to the view
     * @param mixed $data
     * @param string|null $key
     * @return $this
     */
    public function with(mixed $data, ?string $key = null): self
    {
        if (!empty($key)) {
            $this->data[$key] = $data;
            return $this;
        }
        $this->data = array_merge($this->data, (array) $data);
        return $this;
    }

    /**
     * Render's the view file
     * @param string $view View file name
     * @param string $path Directory path of the view file
     * @return string Rendered content
     * @throws \Exception If the view file is not found
     */
    public function render(
        string $view,
        string $path = SDF_APP_VIEW
    ): string {
        $viewFile = $this->resolveView($view, $path);
        $cacheDir = defined('SDF_APP_CACHE') ? SDF_APP_CACHE . 'views/' : sys_get_temp_dir() . '/fuse_cache/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . md5($path . $viewFile) . '.php';

        $sourcePath = $path . $viewFile;
        $cacheMtime = @filemtime($cacheFile);
        $sourceMtime = $this->getSourceMtime($sourcePath);
        if ($cacheMtime === false || $sourceMtime > $cacheMtime) {
            $content = file_get_contents($sourcePath);
            $content = $this->parseContent($content);
            file_put_contents($cacheFile, $content);
        }

        extract($this->data);
        ob_start();
        require $cacheFile;
        return ob_get_clean();
    }

    /**
     * Get source file mtime with static cache (cleared when source changes).
     */
    private function getSourceMtime(string $path): int
    {
        if (!isset(self::$sourceMtimeCache[$path])) {
            self::$sourceMtimeCache[$path] = (int) filemtime($path);
        }
        return self::$sourceMtimeCache[$path];
    }

    /**
     * Resolve the view file with static path cache.
     * @param string $view View file name
     * @param string $path Directory path of the view file
     * @return string Resolved view file
     * @throws \Exception If the view file is not found
     */
    private function resolveView(string $view, string $path): string
    {
        $cacheKey = $path . '|' . $view;
        if (isset(self::$resolvedViewCache[$cacheKey])) {
            return self::$resolvedViewCache[$cacheKey];
        }

        $extensions = [".php", ".phtml", ".fuse"];
        $view = preg_replace("/\.[a-z]+$/", "", $view);
        foreach ($extensions as $ext) {
            if (file_exists($path . $view . $ext)) {
                $resolved = $view . $ext;
                self::$resolvedViewCache[$cacheKey] = $resolved;
                return $resolved;
            }
        }
        throw new \Exception("View file not found: $view");
    }

    /**
     * Parse the content of the view file
     * @param string $input Content of the view file
     * @return string Parsed content
     */
    private function parseContent(string $input): string
    {
        $parsers = [
            // @Foreach directive
            "/\@Foreach ?\((.*?)\)((.|\n)*?)\@endForeach/" => function (
                $matches
            ) {
                return "<?php foreach ({$matches[1]}): ?>{$matches[2]}<?php endforeach; ?>";
            },

            // @If directive
            '/\@If ?\((.*?)\)((.|\n)*?)\@endIf/' => function ($matches) {
                $content = preg_replace(
                    "/\@Else\b/",
                    "<?php else: ?>",
                    $matches[2]
                );
                $content = preg_replace(
                    "/\@ElseIf ?\((.*?)\)/",
                    "<?php elseif ($1): ?>",
                    $content
                );
                return "<?php if ({$matches[1]}): ?>$content<?php endif; ?>";
            },

            // @For directive
            "/\@For ?\((.*?)\)((.|\n)*?)\@endFor/" => function ($matches) {
                return "<?php for ({$matches[1]}): ?>{$matches[2]}<?php endfor; ?>";
            },

            // @While directive
            "/\@While ?\((.*?)\)((.|\n)*?)\@endWhile/" => function ($matches) {
                return "<?php while ({$matches[1]}): ?>{$matches[2]}<?php endwhile; ?>";
            },

            // {{ variable }} directive
            "/{{ ?(.*?) ?}}/" => function ($matches) {
                return "<?php echo htmlspecialchars({$matches[1]}, ENT_QUOTES); ?>";
            },

            // @var directive
            "/@var ?(.*);/" => function ($matches) {
                return "<?php {$matches[1]}; ?>";
            },
        ];

        foreach ($parsers as $pattern => $replacement) {
            $input = preg_replace_callback($pattern, $replacement, $input);
        }

        return $input;
    }
}
