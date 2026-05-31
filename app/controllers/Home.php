<?php

/**
 * Example controller.
 * @warn Consider removing $this->getConfig();
 * to $this->getConfig('key_for_wanted_config')
 * for security reasons.
 */

use SDF\Controller;

class Home extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Index view
     * @return void
     * @throws Exception
     */
    public function index(): void
    {
        $this->load->view("home", $this->getConfig());
    }

}
