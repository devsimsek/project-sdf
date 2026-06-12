<?php

/**
 * smskSoft SDF Schema Foreign Key Definition
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Schema
 * @file        ForeignKeyDefinition.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/schema
 * @since       Version 2.3
 * @filesource
 */

namespace SDF\Schema;

/**
 * Fluent helper for defining a foreign key constraint.
 *
 * Returned by Blueprint::foreign() to allow method chaining:
 *   $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
 */
class ForeignKeyDefinition
{
    private string $column;
    private string $references;
    private string $on;
    private string $onDelete = 'NO ACTION';
    private string $onUpdate = 'NO ACTION';

    /**
     * @param string $column The local column that holds the foreign key.
     */
    public function __construct(string $column)
    {
        $this->column = $column;
    }

    /**
     * Set the referenced column in the foreign table.
     *
     * @param string $column Column name in the referenced table.
     * @return $this
     */
    public function references(string $column): static
    {
        $this->references = $column;
        return $this;
    }

    /**
     * Set the referenced table.
     *
     * @param string $table Table name to reference.
     * @return $this
     */
    public function on(string $table): static
    {
        $this->on = $table;
        return $this;
    }

    /**
     * Set the ON DELETE action (e.g. CASCADE, SET NULL, NO ACTION).
     *
     * @param string $action The action keyword.
     * @return $this
     */
    public function onDelete(string $action): static
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    /**
     * Set the ON UPDATE action (e.g. CASCADE, SET NULL, NO ACTION).
     *
     * @param string $action The action keyword.
     * @return $this
     */
    public function onUpdate(string $action): static
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    /**
     * Return the full foreign key definition as an associative array.
     *
     * @return array{column: string, references: string, on: string, onDelete: string, onUpdate: string}
     */
    public function getDefinition(): array
    {
        return [
            'column'   => $this->column,
            'references' => $this->references,
            'on'       => $this->on,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate,
        ];
    }
}
