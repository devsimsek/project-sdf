<?php

/**
 * Example controller.
 */
class Home extends SDF\Controller
{
    /**
     * Not necessary to add, but
     * it feels kinda nice to
     * control all variables
     * flowing through
     * controller.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Index view
     * @return void
     */
    public function index()
    {
        $this->load->view('home', $this->get_config());
    }
}