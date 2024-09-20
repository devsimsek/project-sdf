<?php

/**
 * Example controller.
 * @warn Consider removing $this->get_config();
 * to $this->get_config('key_for_wanted_config')
 * for security reasons.
 */

class Home extends SDF\Controller
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
    $this->load->view("home", $this->get_config());
  }

}
