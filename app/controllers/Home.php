<?php

/**
 * Example controller.
 * @warn Consider removing $this->get_config();
 * to $this->get_config('key_for_wanted_config')
 * for security reasons.
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
