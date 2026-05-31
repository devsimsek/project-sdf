<?php

namespace SDF;

// Warning, To be deprecated, use Spark\Model instead

/**
 * Model Boilerplate.
 * Add your custom model codes to this file
 * @property Loader $load
 */
class Model
{
    use CoreUtilities;


    public object $load;

    public function __construct()
    {
        $this->load = &self::coreLoadClass("Loader");
    }
}
