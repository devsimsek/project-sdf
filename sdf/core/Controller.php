<?php

namespace SDF;

/**
 * @property object $load
 */
class Controller extends Core
{

    public object $load;

    public function __construct()
    {
        // To access loaded models,
        // libraries and so.
        $this->load =& self::core_loadClass('Loader');
    }

    public function get_config(string $key = null)
    {
        return self::core_getConfig('app', $key);
    }

}