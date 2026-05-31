<?php

use SDF\Model;

class Example extends Model
{
    public function __construct()
    {
        parent::__construct();
        error_log("Loaded model: Example");
    }
}
