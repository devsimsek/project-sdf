<?php

class Example extends SDF\Model
{
    public function __construct()
    {
        parent::__construct();
        error_log("Loaded model: Example");
    }
}