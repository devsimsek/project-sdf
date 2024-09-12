<?php

namespace SDF;

/**
 * smskSoft SDF Response
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Response.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#response
 * @since       Version 1.0
 * @filesource
 */
class Response extends Core
{
  // Array to store headers
  protected array $headers = [];

  // HTTP status code (e.g., 200 for OK, 400 for Bad Request)
  protected int $httpCode;

  /**
   * Set the HTTP status code for the response.
   *
   * @param int $httpCode
   * @return void
   */
  public function setHttpCode(int $httpCode): void
  {
    $this->httpCode = $httpCode;
  }

  /**
   * Add a header to the response.
   *
   * @param string $header
   * @return void
   */
  public function addHeader(string $header): void
  {
    $this->headers[] = $header;
  }

  /**
   * Build and send all headers.
   *
   * @return void
   */
  protected function sendHeaders(): void
  {
    if (empty($this->httpCode)) {
      http_response_code(500);
      die("HTTP code not set. Cannot send headers.");
    }

    if (headers_sent()) {
      http_response_code(500);
      die(
      "Headers have already been sent. Cannot send additional headers."
      );
    }

    // Send all stored headers
    foreach ($this->headers as $header) {
      header($header);
    }

    // Set the HTTP response code
    http_response_code($this->httpCode);
  }

  /**
   * Send the response as JSON.
   *
   * @param mixed $object
   * @param int|null $httpCode
   * @return void
   */
  public function json(mixed $object, int $httpCode = null): void
  {
    // Add the content type header for JSON
    $this->addHeader("Content-Type: application/json");

    // If a specific HTTP code is provided, use it
    if ($httpCode) {
      $this->setHttpCode($httpCode);
    } else {
      // If no HTTP code is provided, default to 200 OK
      $this->setHttpCode(200);
    }

    // Send headers
    $this->sendHeaders();

    // Output the JSON-encoded response
    echo json_encode($object);
  }

  /**
   * Send the response as plain text.
   *
   * @param string $message
   * @param int|null $httpCode
   * @return void
   */
  public function text(string $message, int $httpCode = null): void
  {
    // Add the content type header for plain text
    $this->addHeader("Content-Type: text/plain");

    // If a specific HTTP code is provided, use it
    if ($httpCode) {
      $this->setHttpCode($httpCode);
    }

    // Send headers
    $this->sendHeaders();

    // Output the response message as plain text
    echo $message;
  }

  /**
   * Send the response as HTML.
   *
   * @param string $html
   * @param int|null $httpCode
   * @return void
   */
  public function html(string $html, int $httpCode = null): void
  {
    // Add the content type header for HTML
    $this->addHeader("Content-Type: text/html");

    // If a specific HTTP code is provided, use it
    if ($httpCode) {
      $this->setHttpCode($httpCode);
    }

    // Send headers
    $this->sendHeaders();

    // Output the response message as HTML
    echo $html;
  }
}
