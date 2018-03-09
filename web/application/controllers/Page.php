<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Page extends CI_Controller 
{

	public function index()
	{
		$view_data = array();
		$this->load->view('default',$view_data);
	}

}