<?php

/**
 * smskSoft SDF Spark Seeder
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Spark
 * @file        Seeder.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/spark.md
 * @since       Version 2.1
 * @filesource
 */

namespace SDF\Spark;

abstract class Seeder
{
    /**
     * Run the database seed.
     *
     * @param \PDO $pdo
     * @return void
     */
    abstract public function run(\PDO $pdo): void;
}
