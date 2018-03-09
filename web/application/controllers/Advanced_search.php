<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Advanced_search extends CI_Controller 
{
    // Access level: auth_admin_level
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('advanced_search');
    }

}
