<?php

class Home extends \SDF\Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('example/home');
    }
}