<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Max-Age: 86400");

defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends CI_Controller {

	public function index($id)
	{
		$companies = $this->mcompany->all();

		echo '<pre>';
		print_r($companies);
		echo '</pre>';
	}

	public function getCompanyByMofNo($id1, $id2)
	{
		header('Content-Type: application/json');
		echo json_encode($this->mcompany->getCompanyByMofNo($id1, $id2));
	}

	public function getOCDS()
	{
		header('Content-Type: application/json');
		echo json_encode($this->mcompany->getOCDS());
	}
}
