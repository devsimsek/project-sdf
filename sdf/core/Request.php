<?php

declare(strict_types=1);

namespace SDF;

/**
 * smskSoft SDF Request
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Request.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#request
 * @since       Version 1.0
 * @filesource
 */
class Request extends Core
{

  /**
   * Get a value from the $_GET superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function get(string $key, mixed $default = null): mixed
  {
    return $_GET[$key] ?? $default;
  }

  /**
   * Get a value from the $_POST superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function post(string $key, mixed $default = null): mixed
  {
    return $_POST[$key] ?? $default;
  }

  /**
   * Get a value from the $_REQUEST superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function request(string $key, mixed $default = null): mixed
  {
    return $_REQUEST[$key] ?? $default;
  }

  /**
   * Get a value from the $_SERVER superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function server(string $key, mixed $default = null): mixed
  {
    return $_SERVER[$key] ?? $default;
  }

  /**
   * Get a value from the $_SESSION superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function session(string $key, mixed $default = null): mixed
  {
    return $_SESSION[$key] ?? $default;
  }

  /**
   * Get a value from the $_COOKIE superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function cookie(string $key, mixed $default = null): mixed
  {
    return $_COOKIE[$key] ?? $default;
  }

  /**
   * Get a value from the $_FILES superglobal array
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function file(string $key, mixed $default = null): mixed
  {
    return $_FILES[$key] ?? $default;
  }

  /**
   * Check if the current request is a POST request
   *
   * @return bool
   */
  public function isPost(): bool
  {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
  }

  /**
   * Check if the current request is a GET request
   *
   * @return bool
   */
  public function isGet(): bool
  {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
  }

  /**
   * Check if the current request is an AJAX request
   *
   * @return bool
   */
  public function isAjax(): bool
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
  }

  /**
   * Check if the current request is made over HTTPS
   *
   * @return bool
   */
  public function isSecure(): bool
  {
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
  }

  /**
   * Check if the current request is made from a mobile device
   *
   * @return bool
   */
  public function isMobile(): bool
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool)preg_match('/(iPhone|iPod|iPad|Android|BlackBerry)/i', $userAgent);
  }

  /**
   * Check if the current request is from a web crawler or bot
   *
   * @return bool
   */
  public function isRobot(): bool
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool)preg_match('/(GoogleBot|Slurp)/i', $userAgent);
  }
}
