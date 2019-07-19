<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends CI_Controller {

	public function index()
	{
		$companies = $this->mcompany->all();

		echo '<pre>';
		print_r($companies);
		echo '</pre>';
	}
}
