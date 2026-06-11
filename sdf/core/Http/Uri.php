<?php

declare(strict_types=1);

namespace SDF\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * smskSoft SDF PSR-7 URI
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Http
 * @file        Uri.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/http.md
 * @since       Version 2.0
 * @filesource
 */
class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    /** @var array<string, int|null> Standard ports per scheme. */
    private const STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * @param string $uri Absolute URI string to parse.
     *
     * @throws InvalidArgumentException When the URI cannot be parsed.
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }

            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? (int) $parts['port'] : null;
            $this->path = $parts['path'] ?? '';
            $this->query = $parts['query'] ?? '';
            $this->fragment = $parts['fragment'] ?? '';

            if (isset($parts['user'])) {
                $this->userInfo = $parts['user'];
                if (isset($parts['pass'])) {
                    $this->userInfo .= ':' . $parts['pass'];
                }
            }
        }
    }

    /**
     * Get the URI scheme.
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Get the URI authority (user@host:port).
     *
     * @return string
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && $this->isNonStandardPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Get the URI user info.
     *
     * @return string
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * Get the URI host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the URI port (null for standard ports).
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->isNonStandardPort() ? $this->port : null;
    }

    /**
     * Get the URI path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the URI query string.
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the URI fragment.
     *
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * @param string $scheme
     *
     * @return static
     */
    public function withScheme(string $scheme): UriInterface
    {
        $new = clone $this;
        $new->scheme = strtolower($scheme);
        return $new;
    }

    /**
     * Return an instance with the specified user info.
     *
     * @param string      $user
     * @param string|null $password
     *
     * @return static
     */
    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $new = clone $this;
        $new->userInfo = $user;
        if ($password !== null) {
            $new->userInfo .= ':' . $password;
        }
        return $new;
    }

    /**
     * Return an instance with the specified host.
     *
     * @param string $host
     *
     * @return static
     */
    public function withHost(string $host): UriInterface
    {
        $new = clone $this;
        $new->host = strtolower($host);
        return $new;
    }

    /**
     * Return an instance with the specified port.
     *
     * @param int|null $port
     *
     * @return static
     *
     * @throws InvalidArgumentException When port is out of range.
     */
    public function withPort(?int $port): UriInterface
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException("Invalid port: $port");
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * Return an instance with the specified path.
     *
     * @param string $path
     *
     * @return static
     *
     * @throws InvalidArgumentException When path is not absolute or empty.
     */
    public function withPath(string $path): UriInterface
    {
        if (!str_starts_with($path, '/') && $path !== '') {
            throw new InvalidArgumentException('Path must be absolute (start with "/") or empty');
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * Return an instance with the specified query string.
     *
     * @param string $query
     *
     * @return static
     */
    public function withQuery(string $query): UriInterface
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * Return an instance with the specified fragment.
     *
     * @param string $fragment
     *
     * @return static
     */
    public function withFragment(string $fragment): UriInterface
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * Return the string representation of the URI.
     *
     * @return string
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;
        if ($authority !== '' && $path !== '' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $uri .= $path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Check if the current port is non-standard for the scheme.
     *
     * @return bool
     */
    private function isNonStandardPort(): bool
    {
        if ($this->port === null) {
            return false;
        }

        return !isset(self::STANDARD_PORTS[$this->scheme])
            || self::STANDARD_PORTS[$this->scheme] !== $this->port;
    }
}
