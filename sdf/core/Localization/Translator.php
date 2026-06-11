<?php

/**
 * smskSoft SDF Translator
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Localization
 * @file        Translator.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/localization
 * @since       Version 2.3
 * @filesource
 */

namespace SDF\Localization;

use SDF\Core;

class Translator
{
    private static ?Translator $instance = null;
    private string $locale;
    private string $fallbackLocale;
    private array $translations = [];

    public function __construct(?string $locale = null, ?string $fallbackLocale = null)
    {
        $config = Core::coreGetConfig('localization') ?? [];
        $this->locale = $locale ?? $config['locale'] ?? 'en';
        $this->fallbackLocale = $fallbackLocale ?? $config['fallback_locale'] ?? 'en';
    }

    public static function getInstance(?string $locale = null, ?string $fallbackLocale = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($locale, $fallbackLocale);
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function load(string $namespace, string $file): void
    {
        $locales = [$this->locale, $this->fallbackLocale];
        foreach (array_unique($locales) as $locale) {
            $path = SDF_APP . "lang/{$locale}/{$file}.php";
            if (is_file($path)) {
                $lines = require $path;
                if (is_array($lines)) {
                    $this->translations[$locale][$namespace] = $lines;
                }
            }
        }
    }

    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;

        $value = $this->resolveKey($key, $locale);
        if ($value === null && $locale !== $this->fallbackLocale) {
            $value = $this->resolveKey($key, $this->fallbackLocale);
        }

        if ($value === null) {
            return $key;
        }

        return $this->makeReplacements($value, $replace);
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale ??= $this->locale;
        return $this->resolveKey($key, $locale) !== null;
    }

    public function set(string $key, string $value, ?string $locale = null): void
    {
        $locale ??= $this->locale;
        $segments = explode('.', $key);
        $namespace = array_shift($segments);

        if (!isset($this->translations[$locale][$namespace])) {
            $this->translations[$locale][$namespace] = [];
        }

        $target = &$this->translations[$locale][$namespace];
        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
        $target = $value;
    }

    public function choice(string $key, int $number, array $replace = [], ?string $locale = null): string
    {
        $line = $this->get($key, $replace, $locale);

        if ($line === $key) {
            return $key;
        }

        $segments = explode('|', $line);
        $result = $this->matchPluralSegment($segments, $number) ?? end($segments);
        $result = $result !== false ? (string) $result : (string) end($segments);

        return $this->makeReplacements($result, [':count' => $number] + $replace);
    }

    private function resolveKey(string $key, string $locale): ?string
    {
        $segments = explode('.', $key);
        $namespace = array_shift($segments);

        if (!isset($this->translations[$locale][$namespace])) {
            $this->load($namespace, $namespace);
        }

        $target = $this->translations[$locale][$namespace] ?? null;
        if ($target === null) {
            return null;
        }

        foreach ($segments as $segment) {
            if (!is_array($target) || !array_key_exists($segment, $target)) {
                return null;
            }
            $target = $target[$segment];
        }

        return is_string($target) ? $target : null;
    }

    private function makeReplacements(string $line, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(':' . $key, (string) $value, $line);
        }
        return $line;
    }

    private function matchPluralSegment(array $segments, int $number): ?string
    {
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($this->matchesExplicitRule($segment, $number)) {
                $pos = strpos($segment, '} ');
                if ($pos !== false) {
                    return substr($segment, $pos + 2);
                }
                $pos = strpos($segment, '}');
                if ($pos !== false) {
                    return substr($segment, $pos + 1);
                }
            }
        }

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (preg_match('/^\{([0-9]+)\}/', $segment)) {
                continue;
            }
            if (str_contains($segment, '}')) {
                $item = $segment;
                $pos = strpos($item, '}');
                if ($pos !== false) {
                    $rangePart = substr($item, 0, $pos);
                    if (str_contains($rangePart, ',')) {
                        continue;
                    }
                }
            }
        }

        $simple = $number === 1 ? ($segments[0] ?? null) : ($segments[1] ?? null);
        return $simple;
    }

    private function matchesExplicitRule(string $segment, int $number): bool
    {
        if (!str_starts_with($segment, '{')) {
            return false;
        }

        $close = strpos($segment, '}');
        if ($close === false) {
            return false;
        }

        $rule = substr($segment, 1, $close - 1);

        if (str_contains($rule, ',')) {
            $parts = explode(',', $rule, 2);
            $start = trim($parts[0]);
            $end = trim($parts[1]);

            if ($start === '' && ctype_digit(ltrim($end, '-'))) {
                return $number <= (int) $end;
            }

            if ($end === '' && ctype_digit(ltrim($start, '-'))) {
                return $number >= (int) $start;
            }

            if (ctype_digit(ltrim($start, '-')) && ctype_digit(ltrim($end, '-'))) {
                return $number >= (int) $start && $number <= (int) $end;
            }

            if ($start === '...' && ctype_digit(ltrim($end, '-'))) {
                return $number <= (int) $end;
            }

            if (ctype_digit(ltrim($start, '-')) && $end === '...') {
                return $number >= (int) $start;
            }
        }

        if (ctype_digit($rule)) {
            return $number === (int) $rule;
        }

        return false;
    }
}
