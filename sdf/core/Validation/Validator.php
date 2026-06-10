<?php

/**
 * smskSoft SDF Validation
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Validation
 * @file        Validator.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/validation.md
 * @since       Version 2.1
 * @filesource
 */

declare(strict_types=1);

namespace SDF\Validation;

class Validator
{
    private array $data;
    private array $rules;
    private array $messages = [];
    private array $customMessages = [];
    private array $aliases = [];
    private array $customRules = [];
    private bool $passes = true;

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        $this->messages = [];
        $this->passes = true;

        foreach ($this->rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            $this->validateField($field, $value, $fieldRules);
        }

        return $this->passes;
    }

    public function passes(): bool
    {
        return $this->passes;
    }

    public function fails(): bool
    {
        return !$this->passes;
    }

    public function errors(): array
    {
        return $this->messages;
    }

    public function setAliases(array $aliases): self
    {
        $this->aliases = $aliases;
        return $this;
    }

    public function setMessages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }

    public function addRule(string $name, callable $callback): self
    {
        $this->customRules[$name] = $callback;
        return $this;
    }

    private function validateField(string $field, mixed $value, array $rules): void
    {
        $hasNullable = in_array('nullable', $rules, true);

        if ($hasNullable && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $ruleDef) {
            [$rule, $params] = $this->parseRule($ruleDef);

            if ($rule === 'nullable') {
                continue;
            }

            $valid = $this->validateRule($field, $value, $rule, $params);

            if (!$valid) {
                $this->passes = false;
                $this->addError($field, $rule, $params);
            }
        }
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            $parts = explode(':', $rule, 2);
            return [$parts[0], explode(',', $parts[1])];
        }
        return [$rule, []];
    }

    private function validateRule(string $field, mixed $value, string $rule, array $params): bool
    {
        if (isset($this->customRules[$rule])) {
            return (bool)($this->customRules[$rule])($value, $params, $this->data);
        }

        return match ($rule) {
            'required' => $value !== null && $value !== '',
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min' => $this->validateMin($value, $params),
            'max' => $this->validateMax($value, $params),
            'between' => $this->validateBetween($value, $params),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'string' => is_string($value),
            'boolean' => in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true),
            'array' => is_array($value),
            'alpha' => is_string($value) && ctype_alpha($value),
            'alpha_num' => is_string($value) && ctype_alnum($value),
            'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'in' => in_array((string)$value, $params, true),
            'confirmed' => isset($this->data["{$field}_confirmation"]) && (string)$value === (string)$this->data["{$field}_confirmation"],
            'same' => isset($params[0]) && isset($this->data[$params[0]]) && (string)$value === (string)$this->data[$params[0]],
            'different' => isset($params[0]) && (!isset($this->data[$params[0]]) || (string)$value !== (string)$this->data[$params[0]]),
            'regex' => isset($params[0]) && is_string($value) && preg_match($params[0], $value) === 1,
            default => true,
        };
    }

    private function validateMin(mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            return true;
        }
        $min = (int)$params[0];
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        if (is_numeric($value)) {
            return (float)$value >= $min;
        }
        if (is_array($value)) {
            return count($value) >= $min;
        }
        return false;
    }

    private function validateMax(mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            return true;
        }
        $max = (int)$params[0];
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        if (is_numeric($value)) {
            return (float)$value <= $max;
        }
        if (is_array($value)) {
            return count($value) <= $max;
        }
        return false;
    }

    private function validateBetween(mixed $value, array $params): bool
    {
        if (!isset($params[1])) {
            return true;
        }
        return $this->validateMin($value, [$params[0]]) && $this->validateMax($value, [$params[1]]);
    }

    private function addError(string $field, string $rule, array $params): void
    {
        $message = $this->customMessages["{$field}.{$rule}"]
            ?? $this->customMessages[$field]
            ?? $this->defaultMessage($field, $rule, $params);

        $this->messages[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, array $params): string
    {
        $label = $this->aliases[$field] ?? str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => "The {$label} field is required.",
            'email' => "The {$label} must be a valid email address.",
            'min' => "The {$label} must be at least {$params[0]}.",
            'max' => "The {$label} must not exceed {$params[0]}.",
            'between' => "The {$label} must be between {$params[0]} and {$params[1]}.",
            'numeric' => "The {$label} must be a number.",
            'integer' => "The {$label} must be an integer.",
            'string' => "The {$label} must be a string.",
            'boolean' => "The {$label} must be true or false.",
            'array' => "The {$label} must be an array.",
            'alpha' => "The {$label} may only contain letters.",
            'alpha_num' => "The {$label} may only contain letters and numbers.",
            'url' => "The {$label} must be a valid URL.",
            'in' => "The selected {$label} is invalid.",
            'confirmed' => "The {$label} confirmation does not match.",
            'same' => "The {$label} must match {$params[0]}.",
            'different' => "The {$label} must differ from {$params[0]}.",
            'regex' => "The {$label} format is invalid.",
            default => "The {$label} field is invalid.",
        };
    }
}
