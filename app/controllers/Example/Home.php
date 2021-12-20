<?php

class Home extends \SDF\Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('example/home', $this->get_config());
    }

    public function test()
    {
        print_r('Welcome again. Magic routing works like a charm');
    }
}