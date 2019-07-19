<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mcompany extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function all()
	{
		$companies = [];
		$rs = $this->db->limit(1000)->get('senarai_syarikat');

		foreach($rs->result() as $r) {
			$companies[] = $r;
		}

		return $companies;
	}

}
