<?php

/**
 * smskSoft SDF Queue - Bootstrap require.
 * Single entry point for the Queue subsystem.
 *
 * @package     SDF
 * @subpackage  SDF Queue
 * @filesource
 */

require_once __DIR__ . '/Job.php';
require_once __DIR__ . '/Queue.php';
require_once __DIR__ . '/DatabaseQueue.php';
require_once __DIR__ . '/RedisQueue.php';
require_once __DIR__ . '/Worker.php';
