<?php

namespace SDF;

/**
 * Fuse View Engine
 * A custom view engine solution.
 * Copyright devsimsek
 * @package     Fuse
 * @file        Fuse.php
 * @version     v1.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2023, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/Fuse
 * @since       Version 1.0
 * @filesource
 *
 * Todo;
 * Section and extend support.
 */
class Fuse
{

  protected array $data = [];

  /**
   * Add an object to the data array.
   *
   * @param mixed $data The object to add.
   * @return self
   */
  public function withObject(mixed $data): self
  {
    $this->data = array_merge($this->data, $data);
    return $this;
  }

  /**
   * Add a key-value pair to the data array.
   *
   * @param string $key The key.
   * @param mixed $value The value.
   * @return self
   */
  public function with(string $key, mixed $value): self
  {
    $this->data[$key] = $value;
    return $this;
  }

  /**
   * Render the view file with the provided data.
   *
   * @param string $view The path to the view file.
   * @param string $directory The base directory for the view file (optional).
   * @return string|bool The rendered content of the view file.
   */
  public function render(string $view, string $directory = SDF_APP_VIEW): string|bool
  {
    if (!str_ends_with($view, '.php')) $view .= '.php';
    $content = file_get_contents($directory . $view);

    // Parse @if, @elseif, @else, @endif directives in the content.
    $content = preg_replace_callback('/@if\s*\((.*?)\)(.*?)@endif/s', function ($matches) {
      $condition = trim($matches[1]);
      $ifContent = $matches[2];

      $ifBlock = '<?php if (' . $condition . '): ?>' . $ifContent;
      $ifBlock = preg_replace_callback('/@elseif\s*\((.*?)\)/', function ($matches) {
        $condition = trim($matches[1]);
        return '<?php elseif (' . $condition . '): ?>';
      }, $ifBlock);
      $ifBlock = preg_replace('/@else/', '<?php else: ?>', $ifBlock);
      $ifBlock .= '<?php endif; ?>';

      return $ifBlock;
    }, $content);

    // Parse @while directives in the content.
    $content = preg_replace_callback('/@while\s*\((.*?)\)(.*?)@endwhile/s', function ($matches) {
      $condition = trim($matches[1]);
      $whileContent = $matches[2];
      return '<?php while (' . $condition . '): ?>' . $whileContent . '<?php endwhile; ?>';
    }, $content);

    // Parse @for directives in the content.
    $content = preg_replace_callback('/@for\s*\((.*?)\)(.*?)@endfor/s', function ($matches) {
      $condition = trim($matches[1]);
      $forContent = $matches[2];
      return '<?php for (' . $condition . '): ?>' . $forContent . '<?php endfor; ?>';
    }, $content);

    // Parse @foreach directives in the content.
    $content = preg_replace_callback('/@foreach\s*\((.*?)\)(.*?)@endforeach/s', function ($matches) {
      $variable = trim($matches[1]);
      $loopContent = $matches[2];

      return '<?php foreach (' . $variable . ') { ?>' . $loopContent . '<?php } ?>';
    }, $content);

    // Parse @var declarations in the content.
    $content = preg_replace_callback('/@var\s+(\$\w+)\s*=\s*(.*?);/', function ($matches) {
      $variable = trim($matches[1]);
      $value = trim($matches[2]);

      return '<?php ' . $variable . ' = ' . $value . '; ?>';
    }, $content);

    // Replace {{ $variable }} placeholders with their values.
    $content = preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) {
      $variable = trim($matches[1]);
      return '<?php echo htmlspecialchars(' . $variable . ', ENT_QUOTES); ?>';
    }, $content);
    $content = preg_replace_callback('/{{\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\[[^\]]*\])*)\s*}}/', function ($matches) {
      $variable = $matches[1];
      return '<?php echo ' . $variable . '; ?>';
    }, $content);

    extract($this->data);
    ob_start();
    eval('?>' . $content);
    return ob_get_clean();
  }
}
