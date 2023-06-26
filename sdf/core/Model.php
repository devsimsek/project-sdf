<?php

namespace SDF;
/**
 * Model Boilerplate.
 * Add your custom model codes to this file
 * @property Loader $load
 */
class Model extends Core
{
    public object $load;

    public function __construct()
    {
        $this->load =& self::core_loadClass('Loader');
    }
}