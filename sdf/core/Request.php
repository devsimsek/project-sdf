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
class Request
{
    use CoreUtilities;

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
    public function post(?string $key, mixed $default = null): mixed
    {
        $contentType = $this->header('Content-Type') ?? "";

        if (str_contains($contentType, "/json") || str_contains($contentType, "+json")) {
            if (!$this->isPost()) {
                return $default;
            }
            $body = file_get_contents("php://input");
            $data = json_decode($body, true);
            if ($key === null) {
                return $data ?? $default;
            }
            return (is_array($data) && array_key_exists($key, $data)) ? $data[$key] : $default;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Get a value from php://input for PUT requests
     *
     * Note: Since PHP does not natively support PUT/PATCH/DELETE data parsing, this method reads the raw input stream.
     * You would need to parse the input manually (e.g., json_decode for JSON payload
     * or parse_str for URL-encoded data) depending on the Content-Type of the request.
     *
     * @param string|null $key
     * @param mixed       $default
     * @return mixed
     */
    public function put(?string $key = null, mixed $default = null): mixed
    {
        if (! $this->isPut()) {
            return $default;
        }

        $contentType = $this->header("Content-Type") ?? "";
        $body = file_get_contents("php://input");

        if (str_contains($contentType, "/json") || str_contains($contentType, "+json")) {
            $data = json_decode($body, true);
            if ($key === null) {
                return $data ?? $default;
            }
            return (is_array($data) && array_key_exists($key, $data)) ? $data[$key] : $default;
        }

        if (str_contains($contentType, "application/x-www-form-urlencoded")) {
            $parsed = [];
            parse_str($body, $parsed);
            if ($key === null) {
                return $parsed ?: $default;
            }
            return array_key_exists($key, $parsed) ? $parsed[$key] : $default;
        }

        return $body !== "" ? $body : $default;
    }


    /**
     * Get a value from PATCH requests
     *
     * @param string|null $key
     * @param mixed       $default
     * @return mixed
     */
    public function patch(?string $key = null, mixed $default = null): mixed
    {
        if (! $this->isPatch()) {
            return $default;
        }

        $contentType = $this->header("Content-Type") ?? "";
        $body = file_get_contents("php://input");

        if (str_contains($contentType, "/json") || str_contains($contentType, "+json")) {
            $data = json_decode($body, true);
            if ($key === null) {
                return $data ?? $default;
            }
            return (is_array($data) && array_key_exists($key, $data)) ? $data[$key] : $default;
        }

        if (str_contains($contentType, "application/x-www-form-urlencoded")) {
            $parsed = [];
            parse_str($body, $parsed);
            if ($key === null) {
                return $parsed ?: $default;
            }
            return array_key_exists($key, $parsed) ? $parsed[$key] : $default;
        }

        return $body !== "" ? $body : $default;
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
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "POST";
    }

    /**
     * Check if the current request is a GET request
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "GET";
    }

    /**
     * Check if the current request is a PUT request
     *
     * Note: Since PHP does not natively support PUT data parsing, this method only checks the request method.
     * To access PUT data, you would need to parse the raw input stream (php://input) manually.
     * @return bool
     */
    public function isPut(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "PUT";
    }

    /**
     * Check if the current request is a PATCH request
     *
     * Note: Since PHP does not natively support PATCH data parsing, this method only checks the request method.
     * To access PATCH data, you would need to parse the raw input stream (php://input) manually.
     * @return bool
     */
    public function isPatch(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "PATCH";
    }

    /**
     * Check if the current request is a DELETE request
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "DELETE";
    }

    /**
     * Check if the current request is an OPTIONS request
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return ($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS";
    }

    /**
     * Check if the current request is an AJAX request
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
    }

    /**
     * Check if the current request is made over HTTPS
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on";
    }

    /**
     * Check if the current request is made from a mobile device
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "";
        return (bool) preg_match(
            "/(iPhone|iPod|iPad|Android|Huawei|Honor|BlackBerry|BB10|PlayBook|Opera Mini|Opera Mobi|OPR|Windows Phone|IEMobile|Silk|Kindle|Mobile)/i",
            $userAgent,
        );
    }

    /**
     * Check if the current request is from a web crawler or bot
     *
     * @return bool
     */
    public function isRobot(): bool
    {
        // todo: switch to a configuration based detection
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "";
        return (bool) preg_match(
            "/bot|crawler|spider|scraper|curl|wget|fetch|facebook|twitter|slurp|scan|checker|monitor|gptbot/i",
            $userAgent,
        );
    }

    /**
     * Get a header value.
     *
     * @param string $name
     * @return string|null
     */
    public function header(string $name): ?string
    {
        if (function_exists("getallheaders")) {
            return getallheaders()[$name] ?? null;
        }

        $key = str_replace("-", "_", strtoupper($name));
        return $_SERVER["HTTP_" . $key] ?? ($_SERVER[$key] ?? null);
    }

    /**
     * Get all headers as an associative array.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        if (function_exists("getallheaders")) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, "HTTP_")) {
                $name = str_replace("_", "-", substr($key, 5));
                $name = ucwords(strtolower($name), "-");
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get the HTTP method of the current request.
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");
    }

    /**
     * Get the request URI path.
     *
     * @return string
     */
    public function path(): string
    {
        $path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
        return $path ?: "/";
    }

    /**
     * Get the full URI (path + query string).
     *
     * @return string
     */
    public function uri(): string
    {
        return $_SERVER["REQUEST_URI"] ?? "/";
    }

    /**
     * Get the full URL of the current request.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $scheme = $this->scheme();
        $host = $this->host();
        $uri = $this->uri();
        return "$scheme://$host$uri";
    }

    /**
     * Get the URL scheme (http or https).
     *
     * @return string
     */
    public function scheme(): string
    {
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
            return "https";
        }
        return "http";
    }

    /**
     * Get the host from the current request.
     *
     * @return string
     */
    public function host(): string
    {
        return $_SERVER["HTTP_HOST"] ??
            ($_SERVER["SERVER_NAME"] ?? "localhost");
    }

    /**
     * Get the port from the current request.
     *
     * @return int
     */
    public function port(): int
    {
        return (int) ($_SERVER["SERVER_PORT"] ?? 80);
    }

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        $proxies = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? null;
        if ($proxies) {
            $ips = explode(",", $proxies);
            return trim($ips[0]);
        }
        return $_SERVER["HTTP_CLIENT_IP"] ?? ($_SERVER["REMOTE_ADDR"] ?? null);
    }

    /**
     * Get the user agent string.
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $_SERVER["HTTP_USER_AGENT"] ?? null;
    }

    /**
     * Get the referer URL.
     *
     * @return string|null
     */
    public function referer(): ?string
    {
        return $_SERVER["HTTP_REFERER"] ?? null;
    }

    /**
     * Get all input data (GET + POST + parsed body).
     *
     * @return array
     */
    public function all(): array
    {
        $input = array_merge($_GET, $_POST);
        $body = $this->json();
        if (is_array($body)) {
            $input = array_merge($input, $body);
        }
        return $input;
    }

    /**
     * Get only the specified keys from input.
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * Get all input except the specified keys.
     *
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Check if the input has a given key.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Check if the input has a given key with a non-empty value.
     *
     * @param string $key
     * @return bool
     */
    public function filled(string $key): bool
    {
        $value = $this->all()[$key] ?? null;
        return $value !== null && $value !== "";
    }

    /**
     * Get a specific input value with an optional default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get query string parameters.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Parse the raw JSON request body.
     *
     * @return mixed
     */
    public function json(): mixed
    {
        $contentType = $this->header("Content-Type") ?? "";
        if (
            !str_contains($contentType, "/json") &&
            !str_contains($contentType, "+json")
        ) {
            return null;
        }
        $body = file_get_contents("php://input");
        if (empty($body)) {
            return null;
        }
        return json_decode($body, true);
    }

    /**
     * Get the bearer token from the Authorization header.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header("Authorization");
        if ($header && preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get the request scheme and host.
     *
     * @return string
     */
    public function origin(): string
    {
        return $this->scheme() . "://" . $this->host();
    }

    /**
     * Create a PSR-7 ServerRequest from this legacy request.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function toPsr(): \Psr\Http\Message\ServerRequestInterface
    {
        $uri = new \SDF\Http\Uri($this->origin() . $this->uri());

        $headers = $this->headers();
        $bodyStr = file_get_contents('php://input') ?: '';

        $psrRequest = new \SDF\Http\ServerRequest(
            $this->method(),
            $uri,
            $headers,
            new \SDF\Http\Stream($bodyStr),
            $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
            $_SERVER,
        );

        $psrRequest = $psrRequest
            ->withQueryParams($_GET)
            ->withCookieParams($_COOKIE)
            ->withParsedBody($_POST ?: null)
            ->withUploadedFiles($this->parseFilesToPsr());

        return $psrRequest;
    }

    /**
     * Build a legacy Request from a PSR-7 ServerRequest.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $psr
     * @return self
     */
    public static function fromPsr(\Psr\Http\Message\ServerRequestInterface $psr): self
    {
        $req = new self();

        // Override superglobals to match PSR-7 input
        $_GET = $psr->getQueryParams();
        $_POST = (array) $psr->getParsedBody();
        $_COOKIE = $psr->getCookieParams();
        $_SERVER['REQUEST_METHOD'] = $psr->getMethod();
        $_SERVER['REQUEST_URI'] = (string) $psr->getUri();

        return $req;
    }

    /**
     * Parse $_FILES into PSR-7 UploadedFile array.
     *
     * @return array<string, \Psr\Http\Message\UploadedFileInterface>
     */
    private function parseFilesToPsr(): array
    {
        $result = [];

        foreach ($_FILES as $key => $spec) {
            if (isset($spec['tmp_name'])) {
                $result[$key] = new \SDF\Http\UploadedFile(
                    $spec['tmp_name'],
                    $spec['error'] ?? \UPLOAD_ERR_NO_FILE,
                    $spec['name'] ?? null,
                    $spec['type'] ?? null,
                    $spec['size'] ?? null,
                );
            }
        }

        return $result;
    }
}
