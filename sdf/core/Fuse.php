<?php

namespace SDF;

/**
 * Fuse View Engine
 * A custom view Engine
 * for SDF framework.
 *
 * @package     Fuse
 * @file        Fuse.php
 * @version     v1.0.1
 * @author      devsimsek
 * @copyright   Copyright (C) 2023 smskSoft and devsimsek
 * @license     https://devsimsek.mit-license.org
 * @url         https://github.com/devsimsek/Fuse
 * @since       v1.0
 * @filesource
 */
class Fuse
{
  /**
   * Data which stores
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
   *
   */
  public function with(mixed $data, string $key = null): self
  {
    if (!empty($key)) {
      $this->data[$key] = $data;
      return $this;
    }
    $this->data = array_merge($this->data, (array)$data);
    return $this;
  }

  /**
   * Render's the view
   * @return void
   * @throws error
   */
  public function render(string $view, string $path = SDF_APP_VIEW): string|false
  {
    if (!str_ends_with($view, ".php")) {
      if (file_exists($path . $view . ".php")) {
        $view .= ".php";
      }
    }
    if (!str_ends_with($view, ".fuse")) {
      if (file_exists($path . $view . ".fuse")) {
        $view .= ".fuse";
      }
    }
    $content = file_get_contents($path . $view);
    $content = $this->parseForeach($content);
    $content = $this->parseIf($content);
    $content = $this->parseFor($content);
    $content = $this->parseForeach($content);
    $content = $this->parseWhile($content);
    $content = $this->parseVariable($content);
    $content = $this->parseVar($content);

    extract($this->data);
    ob_start();
    eval('?>' . $content);
    return ob_get_clean();
  }

  private function parseForeach(string $input): string
  {
    return preg_replace_callback("/\@Foreach ?\((.*)\)((.|\n)*)\@endForeach/", function ($matches) {
      return "<?php foreach ($matches[1]): ?>$matches[2]<?php endforeach; ?>";
    }, $input);
  }

  private function parseIf(string $input)
  {
    return preg_replace_callback("/\@If ?\((.*)\)((.|\n)*)\@endIf/", function ($matches) {
      $matches[2] = preg_replace_callback("/\@Else\b/", function () {
        return '<?php else: ?>';
      }, $matches[2]);
      $matches[2] = preg_replace_callback("/\@ElseIf ?\((.*)\)/", function ($m) {
        return "<?php elseif ($m[1]): ?>";
      }, $matches[2]);
      return "<?php if ($matches[1]): ?>$matches[2]<?php endif; ?>";
    }, $input);
  }

  private function parseFor(string $input)
  {
    return preg_replace_callback("/\@For ?\((.*)\)((.|\n)*)\@endFor/", function ($matches) {
      return "<?php for ($matches[1]): ?>$matches[2]<?php endfor; ?>";
    }, $input);
  }

  private function parseWhile(string $input)
  {
    return preg_replace_callback("/\@While ?\((.*)\)((.|\n)*)\@endWhile/", function ($matches) {
      return "<?php while ($matches[1]): ?>$matches[2]<?php endwhile; ?>";
    }, $input);
  }

  private function parseVariable(string $input)
  {
    return preg_replace_callback('/{{ ?(.*?) ?}}/', function ($matches) {
      $variable = trim($matches[1]);
      return '<?php echo htmlspecialchars(' . $variable . ', ENT_QUOTES); ?>';
    }, $input);
  }

  private function parseVar(string $input)
  {
    return preg_replace_callback('/@var ?(.*);/', function ($matches) {
      $variable = trim($matches[1]);
      $value = trim($matches[2]);

      return '<?php ' . $variable . ' = ' . $value . '; ?>';
    }, $input);
  }
}
