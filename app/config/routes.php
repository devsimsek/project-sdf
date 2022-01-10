<?php
/**
 * Example routing schema,
 * $config['path/{pattern}'] = 'controller/method';
 * Pattern Shortcuts;
 * {url}, {id}, {all}
 * or
 * $config['path/{pattern}'] = ['controller/method', 'request_type'];
 * request_type = User request type. such as post, get and delete.
 */
$config['/'] = 'home/index';