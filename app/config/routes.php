<?php

/**
 * Example routing schema,
 *
 * String — single route, matches any method:
 *   $config['path/{pattern}'] = 'controller/method';
 *
 * Array — multiple methods on the same path:
 *   $config['path/{pattern}'] = [
 *       ['controller/method', 'GET'],
 *       ['controller/method', 'POST'],
 *   ];
 *
 * Pattern Shortcuts:
 *   {url}, {id}, {all}
 *
 * @var array $config
 */
$config["/"] = "home";
