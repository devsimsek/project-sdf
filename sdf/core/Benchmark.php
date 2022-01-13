<?php

namespace SDF;

/**
 * smskSoft SDF Benchmark
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Benchmark.php
 * @version     v1.0.0 Early-Alpha Release
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core#benchmark
 * @since       Version 1.0
 * @filesource
 */
class Benchmark extends Core
{

    /**
     * List of all benchmark markers
     *
     * @var array
     */
    public array $marker = array();

    /**
     * Set a benchmark marker
     *
     * Multiple calls to this function can be made so that several
     * execution points can be timed.
     *
     * @param string $name Marker name
     * @return void
     */
    public function mark($name)
    {
        $this->marker[$name] = microtime(TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Elapsed time
     *
     * Calculates the time difference between two marked points.
     *
     * If the first parameter is empty this function instead returns the
     * {elapsed_time} pseudo-variable. This permits the full system
     * execution time to be shown in a template. The output class will
     * swap the real value for this variable.
     *
     * @param string $point1 A particular marked point
     * @param string $point2 A particular marked point
     * @param int $decimals Number of decimal places
     *
     * @return string Calculated elapsed time on success,
     * an '{elapsed_string}' if $point1 is empty
     * or an empty string if $point1 is not found.
     */
    public function elapsed_time($point1 = '', $point2 = '', $decimals = 4): string
    {
        if ($point1 === '') {
            return '{elapsed_time}';
        }
        if (!isset($this->marker[$point1])) {
            return '';
        }
        if (!isset($this->marker[$point2])) {
            $this->marker[$point2] = microtime(TRUE);
        }
        return number_format($this->marker[$point2] - $this->marker[$point1], $decimals);
    }

    // --------------------------------------------------------------------

    /**
     * Memory Usage
     *
     * Simply returns the {memory_usage} marker.
     *
     * This permits it to be put it anywhere in a template
     * without the memory being calculated until the end.
     * The output class will swap the real value for this variable.
     *
     * @return string '{memory_usage}'
     */
    public function memory_usage(): string
    {
        return '{memory_usage}';
    }

}